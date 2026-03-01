<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlanService;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Show the current plan details and usage statistics.
     * Displayed as a settings sub-page (col-3 menu + col-9 content).
     */
    public function index()
    {
        $usageStats = $this->planService->getUsageStats();
        $currentPlan = $this->planService->getCurrentPlan();
        $plans = $this->planService->getAvailablePlans();
        $features = config('plans.features');

        return view('admin.plan.index', compact('usageStats', 'currentPlan', 'plans', 'features'));
    }

    /**
     * Show the upgrade page with plan comparison.
     */
    public function upgrade(Request $request)
    {
        $currentPlan = $this->planService->getCurrentPlan();
        $plans = $this->planService->getAvailablePlans();
        $features = config('plans.features');
        $feature = $request->query('feature');
        $suggestedPlan = $feature ? $this->planService->getSuggestedUpgrade($feature) : null;
        $upgradeMessage = $feature ? (config("plans.upgrade_messages.{$feature}") ?? null) : null;

        return view('admin.plan.upgrade', compact(
            'currentPlan',
            'plans',
            'features',
            'feature',
            'suggestedPlan',
            'upgradeMessage'
        ));
    }

    /**
     * Change the tenant's plan.
     */
    public function change(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|exists:plans,slug',
        ]);

        $tenant = app('currentTenant');
        $result = $this->planService->upgradePlan($tenant, $request->input('plan'));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return redirect()->route('admin.setting.plan')
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
