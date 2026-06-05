<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
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
        ];

        return view('superadmin.subscriptions.index', compact('subscriptions', 'plans', 'stats'));
    }
}
