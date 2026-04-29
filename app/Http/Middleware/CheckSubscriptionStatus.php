<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * Blocks access to the entire app if the trial is expired or subscription is inactive,
     * EXCEPT for the plan management routes so the user can upgrade.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allowed routes that are always accessible even when expired
        $allowedRoutes = [
            'admin.setting.plan',
            'admin.setting.plan.upgrade',
            'admin.setting.plan.change',
            'admin.setting.plan.checkout',
            'admin.setting.plan.confirm-payment',
            'api.payment.confirm',
            'admin.login',
            'admin.logout',
            'admin.login.post',
            // Allow API endpoints needed for the upgrade page
            'api.plan.current',
            'api.plans'
        ];

        // Check if route has a name before checking
        $routeName = $request->route()->getName() ?? '';
        
        // Allow unauthenticated users to access login and related auth routes
        if (!auth()->check() && Str::startsWith($routeName, 'admin.login')) {
            return $next($request);
        }
        
        if (in_array($routeName, $allowedRoutes)) {
            return $next($request);
        }

        // If user is not authenticated, let them through (auth middleware will handle it)
        if (!auth()->check()) {
            return $next($request);
        }

        // Get the tenant - skip check if no tenant context
        try {
            $tenant = app('currentTenant');
        } catch (\Exception $e) {
            return $next($request);
        }

        if ($tenant === null) {
            return $next($request);
        }

        // Backward compatibility
        if (! $tenant->hasPlan() && config('plans.legacy_full_access', true)) {
            return $next($request);
        }

        if ($tenant->hasPlan() && $tenant->planInactive()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => __('plan.notif_trial_expired_desc'),
                    'error' => 'subscription_expired',
                    'redirect' => route('admin.setting.plan.upgrade')
                ], 403);
            }

            return redirect()->route('admin.setting.plan.upgrade')
                ->with('error', __('plan.notif_trial_expired_desc'));
        }

        return $next($request);
    }
}
