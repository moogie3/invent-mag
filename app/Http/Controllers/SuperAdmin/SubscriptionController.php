<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Tenant::with('plan')
            ->whereNotNull('plan_id')
            ->latest()
            ->paginate(20);

        $plans = Plan::all();

        $stats = [
            'total' => Tenant::whereNotNull('plan_id')->count(),
            'active' => Tenant::whereNotNull('plan_id')
                ->where(function($q) {
                    $q->whereNull('plan_expires_at')
                      ->orWhere('plan_expires_at', '>', now());
                })->count(),
            'trialing' => Tenant::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())->count(),
            'expired' => Tenant::whereNotNull('plan_id')
                ->where('plan_expires_at', '<', now())->count(),
            'terminated' => Tenant::where('plan_status', 'terminated')->count(),
        ];

        return view('superadmin.subscriptions.index', compact('subscriptions', 'plans', 'stats'));
    }

    public function terminate(Tenant $tenant)
    {
        if ($tenant->plan_status === 'terminated') {
            return redirect()->route('superadmin.subscriptions.index')
                ->with('error', "Subscription for '{$tenant->name}' is already terminated.");
        }

        \DB::transaction(function () use ($tenant) {
            // Void all pending/waiting orders for this tenant
            SubscriptionOrder::where('tenant_id', $tenant->id)
                ->whereIn('status', ['pending', 'waiting'])
                ->update(['status' => 'voided']);

            // Cancel any scheduled downgrade and clear proration
            $tenant->cancelScheduledDowngrade();

            // Terminate the subscription
            $tenant->update([
                'plan_status' => 'terminated',
                'plan_expires_at' => now(),
                'billing_period_start' => null,
                'billing_period_end' => null,
                'proration_credit' => null,
            ]);
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->log("Subscription terminated for tenant '{$tenant->name}'");

        return redirect()->route('superadmin.subscriptions.index')
            ->with('success', "Subscription terminated for '{$tenant->name}'. All pending orders voided and scheduled changes cleared.");
    }

    public function reactivate(Tenant $tenant)
    {
        if ($tenant->plan_status !== 'terminated') {
            return redirect()->route('superadmin.subscriptions.index')
                ->with('error', 'Only terminated subscriptions can be reactivated.');
        }

        if (!$tenant->plan) {
            return redirect()->route('superadmin.subscriptions.index')
                ->with('error', 'Tenant has no plan assigned. Assign a plan first.');
        }

        $billingDays = $tenant->plan->getBillingPeriodDays();

        $tenant->update([
            'plan_status' => 'active',
            'plan_expires_at' => now()->addDays($billingDays),
            'billing_period_start' => now()->startOfDay()->toDateString(),
            'billing_period_end' => now()->addDays($billingDays)->toDateString(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->log("Subscription reactivated for tenant '{$tenant->name}'");

        return redirect()->route('superadmin.subscriptions.index')
            ->with('success', "Subscription reactivated for '{$tenant->name}'.");
    }
}
