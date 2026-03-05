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
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->web(append: [
            \App\Http\Middleware\HandleWorkspaceParam::class,
            \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\DebugModeMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
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
            
            // Check if tenant ID is in session (user logged in via workspace)
            // Safety check: only access session if it exists
            if ($request->hasSession()) {
                $tenantId = $request->session()->get('tenant_id');
                if ($tenantId) {
                    $tenant = \App\Models\Tenant::find($tenantId);
                    if ($tenant) {
                        $tenant->makeCurrent();
                        // Re-attempt the request
                        return redirect($request->getRequestUri());
                    }
                }
            }
            
            // Check if there's a workspace param we can use
            $workspace = $request->query('workspace');
            if ($workspace && $request->path() !== '/') {
                // Try to find tenant
                $tenant = \App\Models\Tenant::where('domain', 'like', strtolower($workspace) . '.%')
                              ->orWhere('name', 'like', '%' . str_replace('-', ' ', $workspace) . '%')
                              ->first();
                
                if ($tenant) {
                    // Store in session to help the next request
                    if ($request->hasSession()) {
                        $request->session()->put('tenant_id', $tenant->id);
                    }
                    
                    $workspaceSlug = explode('.', $tenant->domain)[0];
                    $mainDomain = config('app.url', 'https://invent-mag.up.railway.app');
                    
                    // Only redirect if we're not already on the main domain with this workspace
                    if ($request->fullUrl() !== $mainDomain . '/?workspace=' . $workspaceSlug) {
                        return redirect()->away($mainDomain . '/?workspace=' . $workspaceSlug);
                    }
                }
            }
            $frontendUrl = config('app.frontend_url', 'https://invent-mag-fe.vercel.app');
            return redirect()->away($frontendUrl . '/login');
        });
    })->create();