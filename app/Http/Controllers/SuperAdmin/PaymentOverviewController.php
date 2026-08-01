<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;

class PaymentOverviewController extends Controller
{
    public function index()
    {
        $totalRevenue = SubscriptionOrder::where('status', 'paid')->sum('amount');
        $totalOrders = SubscriptionOrder::count();
        $paidOrders = SubscriptionOrder::where('status', 'paid')->count();
        $pendingOrders = SubscriptionOrder::where('status', 'pending')->count();

        $paidOrders_all = SubscriptionOrder::with('tenant', 'plan')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->get(['amount', 'paid_at', 'payment_info', 'tenant_id', 'plan_id']);

        $revenueByGateway = $paidOrders_all
            ->groupBy(function ($order) {
                $info = $order->payment_info;

                return is_array($info) && isset($info['payment_type'])
                    ? $info['payment_type']
                    : 'unknown';
            })
            ->map(function ($group) {
                return (object) [
                    'gateway' => $group->first()->payment_info['payment_type'] ?? 'unknown',
                    'total' => $group->sum('amount'),
                    'count' => $group->count(),
                ];
            })
            ->values();

        $monthlyRevenue = $paidOrders_all
            ->filter(function ($order) {
                return $order->paid_at && $order->paid_at->greaterThanOrEqualTo(now()->subMonths(12));
            })
            ->groupBy(function ($order) {
                return $order->paid_at->format('Y-m');
            })
            ->map(function ($group, $month) {
                $label = date('M Y', strtotime($month.'-01'));

                return (object) [
                    'month' => $month,
                    'label' => $label,
                    'total' => (float) $group->sum('amount'),
                ];
            })
            ->sortBy('month')
            ->values();

        $chartMonths = [];
        $chartRevenue = [];
        foreach ($monthlyRevenue as $mr) {
            $chartMonths[] = $mr->label;
            $chartRevenue[] = $mr->total;
        }

        $gatewayLabels = [];
        $gatewayData = [];
        foreach ($revenueByGateway as $gw) {
            $gwLabel = ucfirst(str_replace('_', ' ', $gw->gateway));
            $gatewayLabels[] = $gwLabel;
            $gatewayData[] = (float) $gw->total;
        }

        $recentTransactions = SubscriptionOrder::with('tenant', 'plan')
            ->latest()
            ->take(10)
            ->get();

        $stats = compact('totalRevenue', 'totalOrders', 'paidOrders', 'pendingOrders');

        return view('superadmin.payment-overview.index', compact(
            'stats', 'revenueByGateway', 'monthlyRevenue',
            'chartMonths', 'chartRevenue',
            'gatewayLabels', 'gatewayData',
            'recentTransactions'
        ));
    }
}
