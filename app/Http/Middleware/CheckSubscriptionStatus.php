<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
            'admin.logout',
            // Allow API endpoints needed for the upgrade page
            'api.plan.current',
            'api.plans'
        ];

        if (in_array($request->route()->getName(), $allowedRoutes)) {
            return $next($request);
        }

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
