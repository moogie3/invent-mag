<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;

class BillingController extends Controller
{
    public function index()
    {
        $orders = SubscriptionOrder::with('tenant', 'plan')
            ->latest()
            ->paginate(20);

        $stats = [
            'total_revenue' => SubscriptionOrder::where('status', 'paid')->sum('amount'),
            'total_orders' => SubscriptionOrder::count(),
            'paid_orders' => SubscriptionOrder::where('status', 'paid')->count(),
            'pending_orders' => SubscriptionOrder::where('status', 'pending')->count(),
        ];

        return view('superadmin.billing.index', compact('orders', 'stats'));
    }

    public function show(SubscriptionOrder $order)
    {
        $order->load('tenant', 'plan');
        return view('superadmin.billing.show', compact('order'));
    }
}
