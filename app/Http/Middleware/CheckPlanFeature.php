<?php

namespace App\Http\Middleware;

use App\Services\PlanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Handle an incoming request.
     *
     * Checks whether the current tenant's plan includes the required feature.
     * Usage in routes: ->middleware('plan.feature:pos')
     *                  ->middleware('plan.feature:accounting')
     *
     * @param  string  $feature  The feature slug to check (e.g., 'pos', 'accounting')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // Skip check if no tenant context (e.g., central/public routes)
        try {
            $tenant = app('currentTenant');
        } catch (\Exception $e) {
            return $next($request);
        }

        if ($tenant === null) {
            return $next($request);
        }

        // Backward compatibility: tenant has no plan assigned (legacy tenant)
        if (! $tenant->hasPlan() && config('plans.legacy_full_access', true)) {
            return $next($request);
        }

        // Check if tenant's subscription is active
        if ($tenant->hasPlan() && $tenant->planInactive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your subscription has expired. Please renew to continue.',
                    'error' => 'subscription_expired',
                ], 403);
            }

            return redirect()->route('admin.setting.plan')
                ->with('error', 'Your subscription has expired. Please renew your plan to continue.');
        }

        // Check if the plan includes the requested feature
        if (! $tenant->hasFeature($feature)) {
            $upgradeMessage = config("plans.upgrade_messages.{$feature}",
                'This feature is not available on your current plan.');

            $suggestedPlan = $this->planService->getSuggestedUpgrade($feature);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $upgradeMessage,
                    'error' => 'plan_feature_required',
                    'required_feature' => $feature,
                    'suggested_plan' => $suggestedPlan?->slug,
                ], 403);
            }

            return redirect()->route('admin.setting.plan.upgrade')
                ->with('error', $upgradeMessage)
                ->with('required_feature', $feature)
                ->with('suggested_plan', $suggestedPlan?->slug);
        }

        return $next($request);
    }
}
