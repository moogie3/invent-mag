<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;

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

    public function void(SubscriptionOrder $order)
    {
        if ($order->status === 'voided') {
            return back()->with('error', 'Order is already voided.');
        }

        $order->update(['status' => 'voided']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->log("Order #{$order->order_number} voided.");

        return redirect()->route('superadmin.billing.show', $order)
            ->with('success', "Order #{$order->order_number} voided.");
    }
}
