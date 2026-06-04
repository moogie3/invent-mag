<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Providers\FortifyServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            $appUrl = env('APP_URL', 'http://localhost');
            $baseDomain = env('APP_URL_BASE', parse_url($appUrl, PHP_URL_HOST) ?? 'localhost');

            // Super admin routes — registered FIRST so domain-specific routes
            // take priority over the generic root route in web.php
            Route::middleware('web')
                ->domain('super.' . $baseDomain)
                ->withoutMiddleware([
                    \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
                    \App\Http\Middleware\HandleTenantFromPath::class,
                ])
                ->group(base_path('routes/superadmin.php'));

            // Standard tenant routes
            require base_path('routes/web.php');

            // API routes
            require base_path('routes/api.php');

            // Console commands (Artisan)
            require base_path('routes/console.php');
        },
        health: '/up',
    )
    ->withProviders([
        \Spatie\Multitenancy\MultitenancyServiceProvider::class, // Ensure Spatie is loaded before Fortify
        FortifyServiceProvider::class, // Ensure Fortify is loaded
        \App\Providers\TestingServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->web(append: [
            \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
            \App\Http\Middleware\HandleTenantFromPath::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\DebugModeMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \HotwiredLaravel\TurboLaravel\Http\Middleware\TurboMiddleware::class,
        ])->web(prepend: [
            \App\Http\Middleware\SetSessionCookieName::class,
        ]);

        $middleware->api(append: [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            $baseDomain = env('APP_URL_BASE', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?? 'localhost');
            if ($request->getHost() === 'super.' . $baseDomain) {
                return route('superadmin.login');
            }
            return route('admin.login');
        });

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'plan.feature' => \App\Http\Middleware\CheckPlanFeature::class,
            'plan.limit' => \App\Http\Middleware\CheckPlanLimit::class,
            'subscription.active' => \App\Http\Middleware\CheckSubscriptionStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Helper function to handle errors consistently with Turbo Laravel support
        $handleError = function ($request, $code, $message) {
            // For API requests - return JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], $code);
            }
            
            // Always return HTML view (works with Turbo Drive)
            return response()->view("errors.{$code}", [], $code);
        };

        // 400 - Bad Request
        $exceptions->render(function (\BadRequestException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 400, 'Bad Request');
        });

        // 401 - Unauthorized (handled by redirectGuestsTo in middleware config above)

        // 403 - Forbidden
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 403, 'Forbidden');
        });

        // 404 - Route Not Found
        $exceptions->render(function (\Illuminate\Routing\Exceptions\RouteNotFoundException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 404, 'Page not found');
        });

        // 404 - Not Found Http Exception
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 404, 'Page not found');
        });

        // 404 - Method Not Allowed
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 404, 'Method not allowed');
        });

        // 419 - CSRF Token Expired
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 419, 'Page expired due to inactivity');
        });

        // 429 - Too Many Requests
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 429, 'Too many requests');
        });

        // NoCurrentTenant - Multitenancy (must be before Throwable catch-all)
        $exceptions->render(function (\Spatie\Multitenancy\Exceptions\NoCurrentTenant $e, Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Workspace not found or no tenant specified.'], 404);
            }

            // If on the super admin domain, redirect to super admin dashboard
            $appUrl = env('APP_URL', 'http://localhost');
            $baseDomain = env('APP_URL_BASE', parse_url($appUrl, PHP_URL_HOST) ?? 'localhost');
            if ($request->getHost() === 'super.' . $baseDomain) {
                return redirect()->route('superadmin.dashboard');
            }

            // Redirect to frontend login
            $frontendUrl = config('app.frontend_url');
            return redirect()->away($frontendUrl . '/login');
        });

        // Model Not Found
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Illuminate\Http\Request $request) use ($handleError) {
            return $handleError($request, 404, 'Resource not found');
        });

        // 500 - Server Error (catch-all)
        $exceptions->render(function (\Throwable $e, Illuminate\Http\Request $request) use ($handleError) {
            // Let Laravel handle ValidationException natively (redirects back with errors)
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }
            // Don't handle TokenMismatch here (handled above)
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return $handleError($request, 419, 'Page expired due to inactivity');
            }
            return $handleError($request, 500, 'Internal server error');
        });
    })->create();