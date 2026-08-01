<?php

use App\Http\Middleware\CaptureImpersonation;
use App\Http\Middleware\CheckPlanFeature;
use App\Http\Middleware\CheckPlanLimit;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\DebugModeMiddleware;
use App\Http\Middleware\HandleTenantFromPath;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetSessionCookieName;
use App\Providers\FortifyServiceProvider;
use App\Providers\TestingServiceProvider;
use HotwiredLaravel\TurboLaravel\Http\Middleware\TurboMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\RouteNotFoundException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Multitenancy\MultitenancyServiceProvider;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            $appUrl = env('APP_URL', 'http://localhost');
            $baseDomain = env('APP_URL_BASE', parse_url($appUrl, PHP_URL_HOST) ?? 'localhost');

            // Super admin routes — registered FIRST so domain-specific routes
            // take priority over the generic root route in web.php
            Route::middleware('web')
                ->domain('super.'.$baseDomain)
                ->withoutMiddleware([
                    NeedsTenant::class,
                    HandleTenantFromPath::class,
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
        MultitenancyServiceProvider::class, // Ensure Spatie is loaded before Fortify
        FortifyServiceProvider::class, // Ensure Fortify is loaded
        TestingServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->prepend(HandleCors::class);

        $middleware->web(append: [
            NeedsTenant::class,
            HandleTenantFromPath::class,
            SetLocale::class,
            DebugModeMiddleware::class,
            SecurityHeaders::class,
            TurboMiddleware::class,
            CaptureImpersonation::class,
        ])->web(prepend: [
            SetSessionCookieName::class,
        ]);

        $middleware->api(append: [
            'throttle:api',
            SubstituteBindings::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            $baseDomain = env('APP_URL_BASE', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?? 'localhost');
            if ($request->getHost() === 'super.'.$baseDomain) {
                return route('superadmin.login');
            }

            return route('admin.login');
        });

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'guest' => RedirectIfAuthenticated::class,
            'throttle' => ThrottleRequests::class,
            'plan.feature' => CheckPlanFeature::class,
            'plan.limit' => CheckPlanLimit::class,
            'subscription.active' => CheckSubscriptionStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Turbo Drive sends X-Requested-With: XMLHttpRequest with Accept: */*,
        // which causes expectsJson() to return true. Fix: return HTML for Turbo
        // form submissions so @error directives render correctly and prevent logout.
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            if ($request->is('api/*')) {
                return true;
            }
            if ($request->isXmlHttpRequest() && $request->accepts('text/html')) {
                return false;
            }

            return $request->expectsJson();
        });

        // Helper function to handle errors consistently with Turbo Laravel support
        $shouldJson = function ($request) {
            if ($request->is('api/*')) {
                return true;
            }
            if ($request->isXmlHttpRequest() && $request->accepts('text/html')) {
                return false;
            }

            return $request->expectsJson();
        };
        $handleError = function ($request, $code, $message) use ($shouldJson) {
            if ($shouldJson($request)) {
                return response()->json(['message' => $message], $code);
            }

            return response()->view("errors.{$code}", [], $code);
        };

        // 400 - Bad Request
        $exceptions->render(function (BadRequestException $e, Request $request) use ($handleError) {
            return $handleError($request, 400, 'Bad Request');
        });

        // 401 - Unauthorized (handled by redirectGuestsTo in middleware config above)

        // 403 - Forbidden
        $exceptions->render(function (AuthorizationException $e, Request $request) use ($handleError) {
            return $handleError($request, 403, 'Forbidden');
        });

        // 404 - Route Not Found
        $exceptions->render(function (RouteNotFoundException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Page not found');
        });

        // 404 - Not Found Http Exception
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Page not found');
        });

        // 404 - Method Not Allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Method not allowed');
        });

        // 419 - CSRF Token Expired
        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($handleError) {
            return $handleError($request, 419, 'Page expired due to inactivity');
        });

        // 429 - Too Many Requests
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($handleError) {
            return $handleError($request, 429, 'Too many requests');
        });

        // NoCurrentTenant - Multitenancy (must be before Throwable catch-all)
        $exceptions->render(function (NoCurrentTenant $e, Request $request) use ($shouldJson) {
            if ($shouldJson($request)) {
                return response()->json(['message' => 'Workspace not found or no tenant specified.'], 404);
            }

            // If on the super admin domain, redirect to super admin dashboard
            $appUrl = env('APP_URL', 'http://localhost');
            $baseDomain = env('APP_URL_BASE', parse_url($appUrl, PHP_URL_HOST) ?? 'localhost');
            if ($request->getHost() === 'super.'.$baseDomain) {
                return redirect()->route('superadmin.dashboard');
            }

            // Redirect to frontend login
            $frontendUrl = config('app.frontend_url');

            return redirect()->away($frontendUrl.'/login');
        });

        // Model Not Found
        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($handleError) {
            return $handleError($request, 404, 'Resource not found');
        });

        // Database connection issues — render 401 (not authenticated) page
        $exceptions->render(function (PDOException $e, Request $request) use ($handleError) {
            return $handleError($request, 401, 'Service temporarily unavailable');
        });

        $exceptions->render(function (QueryException $e, Request $request) use ($handleError) {
            return $handleError($request, 401, 'Service temporarily unavailable');
        });

        // Maintenance mode (503)
        $exceptions->render(function (HttpException $e, Request $request) use ($handleError) {
            if ($e->getStatusCode() === 503) {
                return $handleError($request, 503, 'Maintenance');
            }

            return null;
        });

        // ValidationException — check raw Accept header directly (not expectsJson)
        // Turbo 8 sends: Accept: text/html, application/xhtml+xml (no X-Requested-With)
        // expectsJson() depends on Symfony's content negotiation which can misclassify.
        $exceptions->render(function (ValidationException $e, Request $request) {
            $accept = $request->header('Accept', '');
            Log::info('ValidationException caught', ['accept' => $accept]);
            if (str_contains($accept, 'text/html')) {
                return redirect()->back()
                    ->withErrors($e->errors(), $e->errorBag)
                    ->withInput();
            }
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }

            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        });

        // 500 - Server Error (catch-all)
        $exceptions->render(function (Throwable $e, Request $request) use ($handleError) {
            // Don't handle TokenMismatch here (handled above)
            if ($e instanceof TokenMismatchException) {
                return $handleError($request, 419, 'Page expired due to inactivity');
            }
            // Don't handle ValidationException here (handled above)
            if ($e instanceof ValidationException) {
                return null;
            }
            // Let AuthenticationException pass through to default handler (redirects to login)
            if ($e instanceof AuthenticationException) {
                return null;
            }

            return $handleError($request, 500, 'Internal server error');
        });
    })->create();
