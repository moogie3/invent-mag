<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;
use App\Models\Tenant;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Tenant::with('plan')
            ->latest()
            ->paginate(20);

        $allTenants = Tenant::all();
        $isDemo = fn (Tenant $t) => str_starts_with($t->name, 'Demo ');

        $stats = [
            'total' => $allTenants->count(),
            'active' => $allTenants->filter(fn (Tenant $t) => $t->plan_status === 'active')->count(),
            'trialing' => $allTenants->filter(fn (Tenant $t) => ! $isDemo($t) && $t->plan_status === 'trialing' && $t->onTrial())->count(),
            'expired' => $allTenants->filter(fn (Tenant $t) => ! $isDemo($t)
                && $t->plan_status !== 'active'
                && $t->plan_status !== 'terminated'
                && $t->planInactive()
            )->count(),
            'terminated' => $allTenants->filter(fn (Tenant $t) => ! $isDemo($t) && $t->plan_status === 'terminated')->count(),
        ];

        return view('superadmin.subscriptions.index', compact('subscriptions', 'stats'));
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

        if (! $tenant->plan) {
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
