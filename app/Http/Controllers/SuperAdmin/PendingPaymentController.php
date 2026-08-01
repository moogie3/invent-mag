<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;

class PendingPaymentController extends Controller
{
    public function index()
    {
        $pendingOrders = SubscriptionOrder::with('tenant', 'plan')
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        $stats = [
            'total_pending' => SubscriptionOrder::where('status', 'pending')->count(),
            'total_amount' => SubscriptionOrder::where('status', 'pending')->sum('amount'),
        ];

        return view('superadmin.pending-payments.index', compact('pendingOrders', 'stats'));
    }

    public function approve(SubscriptionOrder $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be approved.');
        }

        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $tenant = $order->tenant;
        if ($tenant && $order->plan) {
            $tenant->assignPlan($order->plan);
        }

        return redirect()->route('superadmin.pending-payments.index')
            ->with('success', "Payment #{$order->order_number} approved successfully.");
    }

    public function reject(SubscriptionOrder $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be rejected.');
        }

        $order->update(['status' => 'canceled']);

        return redirect()->route('superadmin.pending-payments.index')
            ->with('success', "Payment #{$order->order_number} rejected.");
    }
}
