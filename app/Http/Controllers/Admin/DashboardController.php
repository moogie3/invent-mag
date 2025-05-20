<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get metrics for dashboard
        $totalLiability = Purchase::sum('total');
        $unpaidLiability = Purchase::where('status', 'Unpaid')->sum('total');
        $totalRevenue = $this->getMonthlyRevenue();
        $unpaidRevenue = Sales::where('status', 'Unpaid')->sum('total');
        $monthlySales = Sales::where('status', 'Paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
        $outCountUnpaid = $this->getPurchaseCountByLocation('OUT', 'Unpaid');

        // Get recent activities in the format expected by the view
        $recentSales = Sales::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        $recentPurchases = Purchase::with('supplier')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // Format all data for key metrics
        $keyMetrics = $this->formatKeyMetrics($totalLiability, $unpaidLiability, $totalRevenue, $unpaidRevenue, $monthlySales, $outCountUnpaid);

        // Prepare financial summary items
        $financialItems = $this->prepareFinancialItems($totalLiability, $unpaidLiability, $totalRevenue);

        // Prepare invoice status data
        $invoiceStatusData = $this->prepareInvoiceStatusData();

        // Prepare customer insights data
        $customerInsights = $this->prepareCustomerInsights();

        // Format recent activities
        $recentActivities = $this->formatRecentActivities($recentSales, $recentPurchases);

        return view('admin.dashboard', [
            'topSellingProducts' => $this->getTopSellingProducts(),
            'topCustomers' => $this->getTopCustomers(),
            'topSuppliers' => $this->getTopSuppliers(),
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'recentActivities' => $recentActivities,
            'lowStockCount' => $this->getLowStockCount(),
            'expiringSoonCount' => $this->getExpiringSoonCount(),
            'totalliability' => $totalLiability,
            'countliability' => $unpaidLiability,
            'paidDebtMonthly' => $this->getPaidDebtMonthly(),
            'countRevenue' => $unpaidRevenue,
            'countSales' => $monthlySales,
            'liabilitypaymentMonthly' => $this->getLiabilityPaymentsMonthly(),
            'inCount' => $this->getPurchaseCountByLocation('IN'),
            'outCount' => $this->getPurchaseCountByLocation('OUT'),
            'inCountUnpaid' => $this->getPurchaseCountByLocation('IN', 'Unpaid'),
            'outCountUnpaid' => $outCountUnpaid,
            'totalRevenue' => $totalRevenue,
            'avgDueDays' => $this->getAverageDueDays(),
            'collectionRate' => $this->getCollectionRate(),
            'keyMetrics' => $keyMetrics,
            'financialItems' => $financialItems,
            'invoiceStatusData' => $invoiceStatusData,
            'customerInsights' => $customerInsights
        ]);
    }

    /**
     * Format key metrics data for the dashboard
     */
    private function formatKeyMetrics($totalLiability, $unpaidLiability, $totalRevenue, $unpaidRevenue, $monthlySales, $outCountUnpaid)
    {
        return [
            [
                'title' => 'Remaining Liability',
                'icon' => 'ti-building-warehouse',
                'value' => $unpaidLiability,
                'total' => $totalLiability,
                'format' => 'currency',
                'bar_color' => 'bg-primary',
                'trend_type' => 'inverse',
                'route' => null,
                'percentage' => $totalLiability > 0 ? round(($unpaidLiability / $totalLiability) * 100) : 0,
                'trend' => $unpaidLiability < $totalLiability * 0.5 ? 'positive' : 'negative',
                'trend_label' => $totalLiability > 0 ? round(($unpaidLiability / $totalLiability) * 100) . '%' : '0%',
                'trend_icon' => $unpaidLiability < $totalLiability * 0.5 ? 'ti ti-trending-up' : 'ti ti-trending-down',
                'badge_class' => $unpaidLiability < $totalLiability * 0.5 ? 'bg-success-lt' : 'bg-danger-lt',
            ],
            [
                'title' => 'Account Receivable',
                'icon' => 'ti-moneybag',
                'value' => $unpaidRevenue,
                'total' => $totalRevenue,
                'format' => 'currency',
                'bar_color' => 'bg-green',
                'trend_type' => 'normal',
                'route' => null,
                'percentage' => $totalRevenue > 0 ? round(($unpaidRevenue / $totalRevenue) * 100) : 0,
                'trend' => $unpaidRevenue > $totalRevenue * 0.5 ? 'positive' : 'negative',
                'trend_label' => $totalRevenue > 0 ? round(($unpaidRevenue / $totalRevenue) * 100) . '%' : '0%',
                'trend_icon' => $unpaidRevenue > $totalRevenue * 0.5 ? 'ti ti-trending-up' : 'ti ti-trending-down',
                'badge_class' => $unpaidRevenue > $totalRevenue * 0.5 ? 'bg-success-lt' : 'bg-danger-lt',
            ],
            [
                'title' => 'Monthly Earnings',
                'icon' => 'ti-chart-pie',
                'value' => $monthlySales,
                'total' => null,
                'format' => 'currency',
                'bar_color' => null,
                'trend_type' => 'simple',
                'route' => null,
                'percentage' => 0,
                'trend' => $monthlySales > 0 ? 'positive' : 'neutral',
                'trend_label' => 'This month',
                'trend_icon' => '',
                'badge_class' => $monthlySales > 0 ? 'bg-success-lt' : 'bg-muted-lt',
            ],
            [
                'title' => 'Payment Overdue',
                'icon' => 'ti-alert-triangle',
                'value' => $outCountUnpaid,
                'total' => 5,
                'format' => 'numeric',
                'bar_color' => null,
                'trend_type' => 'threshold',
                'route' => route('admin.sales', ['status' => 'Unpaid']),
                'percentage' => 0,
                'trend' => $outCountUnpaid <= 5 ? 'positive' : 'negative',
                'trend_label' => $outCountUnpaid <= 5 ? 'All good' : 'Action needed',
                'trend_icon' => $outCountUnpaid <= 5 ? 'ti ti-trending-up' : 'ti ti-alert-circle',
                'badge_class' => $outCountUnpaid <= 5 ? 'bg-success-lt' : 'bg-danger-lt',
            ],
        ];
    }

    /**
     * Prepare financial summary items
     */
    private function prepareFinancialItems($totalLiability, $unpaidLiability, $totalRevenue)
    {
        $operatingExpenses = $totalLiability - $unpaidLiability;

        return [
            [
                'label' => 'Total Liabilities',
                'value' => $totalLiability,
                'icon' => 'ti-wallet',
            ],
            [
                'label' => 'This Month Paid Liabilities',
                'value' => $this->getLiabilityPaymentsMonthly(),
                'icon' => 'ti-calendar',
            ],
            [
                'label' => 'Total Account Receivable',
                'value' => $totalRevenue,
                'icon' => 'ti-report-money',
            ],
            [
                'label' => 'This Month Receivable Paid',
                'value' => $this->getPaidDebtMonthly(),
                'icon' => 'ti-coin',
            ],
            [
                'label' => 'Operating Expenses',
                'value' => $operatingExpenses,
                'icon' => 'ti-shopping-cart',
            ],
        ];
    }

    /**
     * Prepare invoice status data
     */
    private function prepareInvoiceStatusData()
    {
        $inCount = $this->getPurchaseCountByLocation('IN');
        $outCount = $this->getPurchaseCountByLocation('OUT');
        $inCountUnpaid = $this->getPurchaseCountByLocation('IN', 'Unpaid');
        $outCountUnpaid = $this->getPurchaseCountByLocation('OUT', 'Unpaid');
        $totalInvoices = ($outCount ?? 0) + ($inCount ?? 0);
        $collectionRate = $this->getCollectionRate();
        $avgDueDays = $this->getAverageDueDays();

        $outPercentage = ($outCount ?? 0) > 0
            ? ((($outCount ?? 0) - ($outCountUnpaid ?? 0)) / ($outCount ?? 1)) * 100
            : 0;

        $inPercentage = ($inCount ?? 0) > 0
            ? ((($inCount ?? 0) - ($inCountUnpaid ?? 0)) / ($inCount ?? 1)) * 100
            : 0;

        return [
            'totalInvoices' => $totalInvoices,
            'collectionRate' => $collectionRate,
            'collectionRateDisplay' => round($collectionRate),
            'outCount' => $outCount ?? 0,
            'inCount' => $inCount ?? 0,
            'outCountUnpaid' => $outCountUnpaid ?? 0,
            'inCountUnpaid' => $inCountUnpaid ?? 0,
            'outPercentage' => $outPercentage,
            'inPercentage' => $inPercentage,
            'avgDueDays' => $avgDueDays
        ];
    }

    /**
     * Prepare customer insights data
     */
    private function prepareCustomerInsights()
    {
        $avgDueDays = $this->getAverageDueDays();
        $collectionRate = $this->getCollectionRate();

        // Payment terms distribution (sample data)
        $paymentTerms = [
            ['term' => 'Net 15', 'count' => 24],
            ['term' => 'Net 30', 'count' => 38],
            ['term' => 'Net 60', 'count' => 16],
            ['term' => 'Direct Payment', 'count' => 12],
        ];
        $totalCustomers = array_sum(array_column($paymentTerms, 'count'));

        $bgColor = $avgDueDays <= 15
            ? 'bg-success'
            : ($avgDueDays <= 30
                ? 'bg-warning'
                : 'bg-danger');

        $percentage = min(100, max(0, ($avgDueDays / 45) * 100));

        return [
            'avgDueDays' => $avgDueDays,
            'collectionRate' => round($collectionRate),
            'paymentTerms' => $paymentTerms,
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => round($totalCustomers * 0.75),
            'bgColor' => $bgColor,
            'percentage' => $percentage
        ];
    }

    /**
     * Format recent activities
     */
    private function formatRecentActivities($recentSales, $recentPurchases)
    {
        $recentActivities = collect();

        foreach ($recentSales as $sale) {
            $recentActivities->push([
                'type' => 'sale',
                'data' => $sale,
                'date' => $sale->created_at,
            ]);
        }

        foreach ($recentPurchases as $purchase) {
            $recentActivities->push([
                'type' => 'purchase',
                'data' => $purchase,
                'date' => $purchase->created_at,
            ]);
        }

        return $recentActivities->sortByDesc('date')->take(5);
    }

    /**
     * Get count of products with low stock
     */
    private function getLowStockCount()
    {
        // Fallback implementation in case Product::lowStockCount() doesn't exist
        if (method_exists(Product::class, 'lowStockCount')) {
            return Product::lowStockCount();
        }

        // Basic implementation - adjust thresholds as needed
        return Product::whereRaw('quantity <= min_stock')->count();
    }

    /**
     * Get count of products expiring soon
     */
    private function getExpiringSoonCount()
    {
        // Fallback implementation in case Product::expiringSoonCount() doesn't exist
        if (method_exists(Product::class, 'expiringSoonCount')) {
            return Product::expiringSoonCount();
        }

        // Basic implementation - products expiring in the next 30 days
        return Product::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->count();
    }

    private function getMonthlyRevenue()
    {
        return Sales::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
    }

    private function getPaidDebtMonthly()
    {
        return Sales::whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->where('status', 'Paid')
            ->sum('total');
    }

    private function getLiabilityPaymentsMonthly()
    {
        return Purchase::whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->where('status', 'Paid')
            ->sum('total');
    }

    private function getPurchaseCountByLocation($location, $status = null)
    {
        $query = Purchase::whereHas('supplier', fn($q) => $q->where('location', $location));
        if ($status) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    private function getTopSellingProducts()
    {
        return SalesItem::select('product_id', DB::raw('SUM(quantity) as units_sold'), DB::raw('SUM(total) as revenue'))
            ->with('product')
            ->whereHas('product')
            ->groupBy('product_id')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get()
            ->map(fn($item) => (object)[
                'id' => $item->product_id,
                'name' => $item->product->name ?? 'Unknown Product',
                'code' => $item->product->code ?? 'N/A',
                'image' => $item->product->image ?? null,
                'category' => $item->product->category ?? null,
                'units_sold' => $item->units_sold,
                'revenue' => $item->revenue
            ]);
    }

    private function getTopCustomers()
    {
        return Sales::select('customer_id', DB::raw('SUM(total) as total_sales'))
            ->with('customer')
            ->whereHas('customer')
            ->groupBy('customer_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(fn($item) => (object)[
                'id' => $item->customer_id,
                'name' => $item->customer->name ?? 'Unknown Customer',
                'total_sales' => $item->total_sales
            ]);
    }

    private function getTopSuppliers()
    {
        return Purchase::select('supplier_id', DB::raw('SUM(total) as total_purchases'))
            ->with('supplier')
            ->whereHas('supplier')
            ->groupBy('supplier_id')
            ->orderByDesc('total_purchases')
            ->limit(5)
            ->get()
            ->map(fn($item) => (object)[
                'id' => $item->supplier_id,
                'name' => $item->supplier->name ?? 'Unknown Supplier',
                'location' => $item->supplier->location ?? 'N/A',
                'total_purchases' => $item->total_purchases
            ]);
    }

    private function getRecentActivity()
    {
        $recentSales = Sales::with('customer')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($sale) => [
                'type' => 'sale',
                'title' => 'New Sale: ' . $sale->invoice,
                'description' => 'To ' . ($sale->customer->name ?? 'Unknown Customer'),
                'amount' => $sale->total,
                'time' => $sale->created_at->diffForHumans(),
                'icon' => 'ti ti-shopping-cart',
                'color' => 'bg-green text-white'
            ]);

        $recentPurchases = Purchase::with('supplier')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($purchase) => [
                'type' => 'purchase',
                'title' => 'New Purchase: ' . $purchase->invoice,
                'description' => 'From ' . ($purchase->supplier->name ?? 'Unknown Supplier'),
                'amount' => $purchase->total,
                'time' => $purchase->created_at->diffForHumans(),
                'icon' => 'ti ti-truck-delivery',
                'color' => 'bg-blue text-white'
            ]);

        $recentPayments = Purchase::where('status', 'Paid')
            ->whereNotNull('payment_date')
            ->orderByDesc('payment_date')
            ->limit(3)
            ->get()
            ->map(fn($payment) => [
                'type' => 'payment',
                'title' => 'Payment Made: ' . $payment->invoice,
                'description' => 'Amount: ' . CurrencyHelper::format($payment->total),
                'amount' => $payment->total,
                'time' => Carbon::parse($payment->payment_date)->diffForHumans(),
                'icon' => 'ti ti-currency-dollar',
                'color' => 'bg-purple text-white'
            ]);

        return $recentSales
            ->concat($recentPurchases)
            ->concat($recentPayments)
            ->sortByDesc(fn($item) => strtotime($item['time']))
            ->take(8)
            ->values()
            ->toArray();
    }

    /**
     * Calculate average days between due_date and payment_date for paid invoices
     *
     * @return int
     */
    private function getAverageDueDays()
    {
        $avgDays = Sales::where('status', 'Paid')
            ->whereNotNull('payment_date')
            ->whereNotNull('due_date')
            ->get()
            ->avg(function ($sale) {
                return $sale->payment_date->diffInDays($sale->due_date);
            });

        return round($avgDays) ?? 0;
    }

    /**
     * Calculate percentage of paid invoices vs total
     *
     * @return int
     */
    private function getCollectionRate()
    {
        $totalInvoices = Sales::count();
        $paidInvoices = Sales::where('status', 'Paid')->count();

        return $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100) : 0;
    }
}