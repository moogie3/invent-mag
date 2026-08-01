<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::whereNotNull('plan_id')
            ->where(function ($q) {
                $q->whereNull('plan_expires_at')
                    ->orWhere('plan_expires_at', '>', now());
            })->count();
        $trialTenants = Tenant::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())->count();

        $activePlans = Plan::where('is_active', true)->count();

        $totalSubscriptions = Tenant::whereNotNull('plan_id')->count();
        $trialSubscriptions = Tenant::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->count();
        $activeSubscriptions = Tenant::whereNotNull('plan_id')
            ->where(function ($q) {
                $q->whereNull('plan_expires_at')
                    ->orWhere('plan_expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '<=', now());
            })
            ->count();

        // Tenants created over last 6 months (chart)
        $tenantsCreatedChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Tenant::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $tenantsCreatedChart['labels'][] = $month->format('Y-m');
            $tenantsCreatedChart['data'][] = $count;
        }

        // Subscriptions by plan
        $subscriptionsByPlan = [];
        $plans = Plan::all();
        foreach ($plans as $plan) {
            $count = Tenant::where('plan_id', $plan->id)->count();
            if ($count > 0) {
                $subscriptionsByPlan[] = [
                    'name' => $plan->name,
                    'count' => $count,
                ];
            }
        }

        // Sort descending
        usort($subscriptionsByPlan, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        // Keep top 5
        $subscriptionsByPlan = array_slice($subscriptionsByPlan, 0, 5);

        return view('superadmin.dashboard.index', compact(
            'totalTenants',
            'activeTenants',
            'trialTenants',
            'activePlans',
            'totalSubscriptions',
            'activeSubscriptions',
            'trialSubscriptions',
            'tenantsCreatedChart',
            'subscriptionsByPlan'
        ));
    }
}
