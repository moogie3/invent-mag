<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Providers\FortifyServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
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
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\DebugModeMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SetSessionCookieName::class,
        ]);

        $middleware->api(append: [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));

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
        $exceptions->render(function (\Spatie\Multitenancy\Exceptions\NoCurrentTenant $e, Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Workspace not found or no tenant specified.'], 404);
            }
            
            // Redirect to frontend login - stop the local loop
            $frontendUrl = config('app.frontend_url');
            return redirect()->away($frontendUrl . '/login');
        });
    })->create();