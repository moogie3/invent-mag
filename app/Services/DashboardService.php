<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\Categories;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData(array $dates = [], $reportType = 'all', $categoryId = null)
    {
        if (empty($dates) || !isset($dates['start']) || !isset($dates['end'])) {
            $dates = $this->calculateDateRange('this_month');
        }
        $totalLiability = Purchase::sum('total');
        $unpaidLiability = Purchase::where('status', 'Unpaid')->sum('total');
        $totalRevenue = $this->getMonthlyRevenue();
        $unpaidRevenue = Sales::where('status', 'Unpaid')->sum('total');
        $monthlySales = Sales::where('status', 'Paid')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $outCountUnpaid = $this->getPurchaseCountByLocation('OUT', 'Unpaid');
        $inCountUnpaid = $this->getPurchaseCountbyLocation('IN', 'Unpaid');
        $recentSales = collect(); // Temporarily empty
        $recentPurchases = collect(); // Temporarily empty

        $keyMetrics = $this->formatKeyMetrics($totalLiability, $unpaidLiability, $totalRevenue, $unpaidRevenue, $monthlySales, $outCountUnpaid, $inCountUnpaid);
        $financialItems = []; // Temporarily empty
        $invoiceStatusData = []; // Temporarily empty
        $customerInsights = []; // Temporarily empty
        $customerAnalytics = []; // Temporarily empty
        $supplierAnalytics = []; // Temporarily empty
        $recentTransactions = collect(); // Temporarily empty
        $topCategories = collect(); // Temporarily empty
        $monthlyData = collect(); // Temporarily empty
        $lowStockProducts = collect(); // Temporarily empty
        $expiringSoonItems = collect(); // Temporarily empty

        $salesData = collect(); // Temporarily empty
        $purchaseData = collect(); // Temporarily empty

        return [
            'topCategories' => $topCategories,
            'monthlyData' => $monthlyData,
            'chartLabels' => $salesData->pluck('month')->toArray(),
            'chartData' => $salesData->pluck('total')->toArray(),
            'purchaseChartLabels' => $purchaseData->pluck('month')->toArray(),
            'purchaseChartData' => $purchaseData->pluck('total')->toArray(),
            'customerAnalytics' => $customerAnalytics,
            'supplierAnalytics' => $supplierAnalytics,
            'recentTransactions' => $recentTransactions,
            'topSellingProducts' => collect(), // Temporarily empty
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'lowStockCount' => 0, // Temporarily 0
            'lowStockProducts' => $lowStockProducts,
            'expiringSoonItems' => $expiringSoonItems,
            'totalliability' => $totalLiability,
            'countliability' => $unpaidLiability,
            'paidDebtMonthly' => 0, // Temporarily 0
            'countRevenue' => $unpaidRevenue,
            'countSales' => $monthlySales,
            'liabilitypaymentMonthly' => 0, // Temporarily 0
            'inCount' => 0, // Temporarily 0
            'outCount' => 0, // Temporarily 0
            'inCountUnpaid' => $inCountUnpaid,
            'outCountUnpaid' => $outCountUnpaid,
            'totalRevenue' => $totalRevenue,
            'avgDueDays' => 0, // Temporarily 0
            'collectionRate' => 0, // Temporarily 0
            'keyMetrics' => $keyMetrics,
            'financialItems' => $financialItems,
            'invoiceStatusData' => $invoiceStatusData,
            'customerInsights' => $customerInsights,
        ];
    }

    public function getChartData($period, $type)
    {
        $dates = $this->convertPeriodToDates($period);

        if ($type === 'sales') {
            $data = $this->getSalesChartData($dates);
        } else {
            $data = $this->getPurchaseChartData($dates);
        }

        return [
            'labels' => $data['labels'],
            'data' => $data['data'],
            'formatted' => $data['formatted'],
        ];
    }

    public function calculateDateRange($dateRange, $startDate = null, $endDate = null)
    {
        $now = Carbon::now();

        switch ($dateRange) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'this_quarter':
                $start = $now->copy()->startOfQuarter();
                $end = $now->copy()->endOfQuarter();
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'custom':
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfMonth();
                $end = $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfMonth();
                break;
            default:
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
        }

        return ['start' => $start, 'end' => $end];
    }

    private function getTopCategories($dates)
    {
        $categoryRevenue = DB::table('sales_items')
            ->join('sales', 'sales_items.sales_id', '=', 'sales.id')
            ->join('products', 'sales_items.product_id', '=', 'products.id')
            ->select(['products.category_id', DB::raw('COUNT(DISTINCT products.id) as products_count'), DB::raw('SUM(sales_items.quantity * sales_items.customer_price) as revenue')])
            ->whereBetween('sales.order_date', [$dates['start'], $dates['end']])
            ->groupBy('products.category_id')
            ->orderBy('revenue', 'desc')
            ->limit(5)
            ->get()
            ->keyBy('category_id');

        $categoryIds = $categoryRevenue->pluck('category_id');
        $categories = Categories::whereIn('id', $categoryIds)->get();

        $totalRevenue = $this->getTotalRevenue($dates);

        return $categories
            ->map(function ($category) use ($categoryRevenue, $totalRevenue) {
                $revenueData = $categoryRevenue->get($category->id);
                $revenue = $revenueData ? $revenueData->revenue : 0;
                $productsCount = $revenueData ? $revenueData->products_count : 0;
                $percentage = $totalRevenue > 0 ? ($revenue / $totalRevenue) * 100 : 0;

                return [
                    'name' => $category->name,
                    'products_count' => $productsCount,
                    'revenue' => $revenue,
                    'percentage' => round($percentage, 1),
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    private function getMonthlyData($dates, $categoryId = null)
    {
        $months = [];
        $current = $dates['start']->copy()->startOfMonth();
        $end = $dates['end']->copy()->endOfMonth();

        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $revenue = $this->getTotalRevenue(['start' => $monthStart, 'end' => $monthEnd], $categoryId);
            $expenses = $this->getTotalPurchases(['start' => $monthStart, 'end' => $monthEnd], $categoryId);
            $profit = $revenue - $expenses;
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            $months[] = [
                'month' => $current->format('M Y'),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $profit,
                'margin' => $margin,
            ];

            $current->addMonth();
        }

        return $months;
    }

    private function getTotalRevenue($dates, $categoryId = null, $paymentStatus = null)
    {
        $query = Sales::whereBetween('order_date', [$dates['start'], $dates['end']]);

        if ($paymentStatus) {
            $query->where('status', $paymentStatus);
        }

        if ($categoryId) {
            $query->whereHas('items.product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        return $query->sum('total') ?? 0;
    }

    private function getTotalPurchases($dates, $categoryId = null)
    {
        $query = Purchase::whereBetween('order_date', [$dates['start'], $dates['end']]);

        if ($categoryId) {
            $query->whereHas('items.product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        return $query->sum('total') ?? 0;
    }

    private function formatKeyMetrics($totalLiability, $unpaidLiability, $totalRevenue, $unpaidRevenue, $monthlySales, $outCountUnpaid, $inCountUnpaid)
    {
        return [
            [
                'title' => __('messages.remaining_liability'),
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
                'title' => __('messages.account_receivable'),
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
                'title' => __('messages.monthly_earnings'),
                'icon' => 'ti-chart-pie',
                'value' => $monthlySales,
                'total' => null,
                'format' => 'currency',
                'bar_color' => null,
                'trend_type' => 'simple',
                'route' => null,
                'percentage' => 0,
                'trend' => $monthlySales > 0 ? 'positive' : 'neutral',
                'trend_label' => __('messages.this_month'),
                'trend_icon' => '',
                'badge_class' => $monthlySales > 0 ? 'bg-success-lt' : 'bg-muted-lt',
            ],
            [
                'title' => __('messages.payment_overdue'),
                'icon' => 'ti-alert-triangle',
                'value' => $this->getOverdueInvoicesCount(),
                'total' => null,
                'format' => 'numeric',
                'bar_color' => null,
                'trend_type' => 'threshold',
                'route' => route('admin.po', ['status' => 'Overdue']),
                'percentage' => 0,
                'trend' => $this->getOverdueInvoicesCount() == 0 ? 'positive' : 'negative',
                'trend_label' => $this->getOverdueInvoicesCount() == 0 ? __('messages.no_overdue_payments') : __('messages.action_required'),
                'trend_icon' => $this->getOverdueInvoicesCount() == 0 ? 'ti ti-check' : 'ti ti-alert-circle',
                'badge_class' => $this->getOverdueInvoicesCount() == 0 ? 'bg-success-lt' : 'bg-danger-lt',
            ],
        ];
    }

    private function getOverdueInvoicesCount()
    {
        return Purchase::where('status', 'Unpaid')->where('due_date', '<', now())->count();
    }
    private function prepareFinancialItems($totalLiability, $unpaidLiability, $totalRevenue)
    {
        $operatingExpenses = $totalLiability - $unpaidLiability;

        return [
            [
                'label' => __('messages.total_liabilities'),
                'value' => $totalLiability,
                'icon' => 'ti-wallet',
            ],
            [
                'label' => __('messages.this_month_paid_liabilities'),
                'value' => $this->getLiabilityPaymentsMonthly(),
                'icon' => 'ti-calendar',
            ],
            [
                'label' => __('messages.total_account_receivable'),
                'value' => $totalRevenue,
                'icon' => 'ti-report-money',
            ],
            [
                'label' => __('messages.this_month_receivable_paid'),
                'value' => $this->getPaidDebtMonthly(),
                'icon' => 'ti-coin',
            ],
            [
                'label' => __('messages.operating_expenses'),
                'value' => $operatingExpenses,
                'icon' => 'ti-shopping-cart',
            ],
        ];
    }

    private function prepareInvoiceStatusData()
    {
        $inCount = $this->getPurchaseCountByLocation('IN');
        $outCount = $this->getPurchaseCountByLocation('OUT');
        $inCountUnpaid = $this->getPurchaseCountByLocation('IN', 'Unpaid');
        $outCountUnpaid = $this->getPurchaseCountByLocation('OUT', 'Unpaid');
        $totalInvoices = ($outCount ?? 0) + ($inCount ?? 0);
        $collectionRate = $this->getCollectionRate();
        $avgDueDays = $this->getAverageDueDays();

        $outPercentage = ($outCount ?? 0) > 0 ? ((($outCount ?? 0) - ($outCountUnpaid ?? 0)) / ($outCount ?? 1)) * 100 : 0;

        $inPercentage = ($inCount ?? 0) > 0 ? ((($inCount ?? 0) - ($inCountUnpaid ?? 0)) / ($inCount ?? 1)) * 100 : 0;

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
            'avgDueDays' => $avgDueDays,
        ];
    }

    private function prepareCustomerInsights()
    {
        $avgDueDays = Sales::where('sales.status', 'Paid')
            ->whereNotNull('sales.due_date')
            ->join('payments', function ($join) {
                $join->on('sales.id', '=', 'payments.paymentable_id')
                    ->where('payments.paymentable_type', Sales::class);
            })
            ->select(DB::raw('AVG(DATEDIFF(payments.payment_date, sales.due_date)) as avg_days'))
            ->value('avg_days') ?? 0;

        $totalInvoices = Sales::count();
        $paidInvoices = Sales::where('status', 'Paid')->count();
        $collectionRate = $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;

        $paymentTermsRaw = Customer::select('payment_terms', DB::raw('count(*) as count'))->groupBy('payment_terms')->get();

        $paymentTerms = $paymentTermsRaw
            ->map(function ($row) {
                return [
                    'term' => $row->payment_terms ?? 'Unknown',
                    'count' => $row->count,
                ];
            })
            ->toArray();

        $totalCustomers = array_sum(array_column($paymentTerms, 'count'));

        $bgColor = $avgDueDays <= 15 ? 'bg-success' : ($avgDueDays <= 30 ? 'bg-warning' : 'bg-danger');

        $percentage = min(100, max(0, ($avgDueDays / 45) * 100));

        return [
            'avgDueDays' => round($avgDueDays),
            'collectionRate' => round($collectionRate),
            'paymentTerms' => $paymentTerms,
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $totalCustomers,
            'bgColor' => $bgColor,
            'percentage' => $percentage,
        ];
    }

    private function convertPeriodToDates($period)
    {
        $now = Carbon::now();
        switch ($period) {
            case '7days':
                return ['start' => $now->copy()->subDays(7), 'end' => $now];
            case '30days':
                return ['start' => $now->copy()->subDays(30), 'end' => $now];
            case '3months':
                return ['start' => $now->copy()->subMonths(3), 'end' => $now];
            case 'year':
                return ['start' => $now->copy()->subYear(), 'end' => $now];
            default:
                return ['start' => $now->copy()->subDays(30), 'end' => $now];
        }
    }

    private function getSalesChartData($dates)
    {
        $salesData = Sales::selectRaw('DATE(order_date) as date, SUM(total) as total')
            ->whereBetween('order_date', [$dates['start'], $dates['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $salesData->pluck('date')->toArray(),
            'data' => $salesData->pluck('total')->toArray(),
            'formatted' => $salesData->pluck('total')->map(fn($val) => number_format($val))->toArray(),
        ];
    }

    private function getPurchaseChartData($dates)
    {
        $purchaseData = Purchase::selectRaw('DATE(order_date) as date, SUM(total) as total')
            ->whereBetween('order_date', [$dates['start'], $dates['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $purchaseData->pluck('date')->toArray(),
            'data' => $purchaseData->pluck('total')->toArray(),
            'formatted' => $purchaseData->pluck('total')->map(fn($val) => number_format($val))->toArray(),
        ];
    }

    private function getLowStockCount()
    {
        if (method_exists(Product::class, 'lowStockCount')) {
            return Product::lowStockCount();
        }

        return Product::whereRaw('quantity <= min_stock')->count();
    }

    private function getMonthlyRevenue()
    {
        return Sales::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
    }

    private function getPaidDebtMonthly()
    {
        return Sales::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->where('status', 'Paid')->sum('total');
    }

    private function getLiabilityPaymentsMonthly()
    {
        return Purchase::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->where('status', 'Paid')->sum('total');
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
        return SalesItem::select('product_id', DB::raw('SUM(quantity) as units_sold'), DB::raw('SUM(total) as revenue'))->with('product')->whereHas('product')->groupBy('product_id')->orderByDesc('units_sold')->limit(5)->get()->map(
            fn($item) => (object) [
                'id' => $item->product_id,
                'name' => $item->product->name ?? 'Unknown Product',
                'code' => $item->product->code ?? 'N/A',
                'image' => $item->product->image ?? null,
                'category' => $item->product->category ?? null,
                'units_sold' => $item->units_sold,
                'revenue' => $item->revenue,
            ],
        );
    }

    private function getCustomerAnalytics($dates)
    {
        $totalCustomers = Customer::count();

        $activeCustomers = Customer::whereHas('sales', function ($query) use ($dates) {
            $query->whereBetween('order_date', [$dates['start'], $dates['end']]);
        })->count();

        $previousPeriodStart = $dates['start']->copy()->subMonths(1);
        $previousPeriodEnd = $dates['end']->copy()->subMonths(1);

        $currentPeriodCustomers = Customer::whereHas('sales', function ($query) use ($dates) {
            $query->whereBetween('order_date', [$dates['start'], $dates['end']]);
        })->pluck('id');

        $previousPeriodCustomers = Customer::whereHas('sales', function ($query) use ($previousPeriodStart, $previousPeriodEnd) {
            $query->whereBetween('order_date', [$previousPeriodStart, $previousPeriodEnd]);
        })->pluck('id');

        $retainedCustomers = $currentPeriodCustomers->intersect($previousPeriodCustomers)->count();
        $retentionRate = $previousPeriodCustomers->count() > 0 ? ($retainedCustomers / $previousPeriodCustomers->count()) * 100 : 0;

        $avgOrderValue = Sales::whereBetween('order_date', [$dates['start'], $dates['end']])->avg('total') ?? 0;

        $customerLifetimeValue = $totalCustomers > 0 ? Sales::sum('total') / $totalCustomers : 0;

        $topCustomers = Sales::select('customer_id', DB::raw('SUM(total) as total_sales'))->with('customer')->whereHas('customer')->groupBy('customer_id')->orderByDesc('total_sales')->limit(5)->get()->map(
            fn($item) => (object) [
                'id' => $item->customer_id,
                'name' => $item->customer->name ?? 'Unknown Customer',
                'total_sales' => $item->total_sales,
            ],
        );

        return [
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'retentionRate' => round($retentionRate, 1),
            'avgOrderValue' => $avgOrderValue,
            'customerLifetimeValue' => $customerLifetimeValue,
            'topCustomers' => $topCustomers,
        ];
    }

    private function getSupplierAnalytics($dates)
    {
        $totalSuppliers = Supplier::count();

        $activeSuppliers = Supplier::whereHas('purchases', function ($query) use ($dates) {
            $query->whereBetween('order_date', [$dates['start'], $dates['end']]);
        })->count();

        $totalPurchases = Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])->count();
        $paidPurchases = Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])
            ->where('status', 'Paid')
            ->count();

        $supplierPaymentPerformance = $totalPurchases > 0 ? ($paidPurchases / $totalPurchases) * 100 : 0;

        $avgPurchaseValue = Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])->avg('total') ?? 0;

        $totalOutstanding =
            Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])
                ->whereIn('status', ['Unpaid', 'Partial'])
                ->sum('total') ?? 0;

        $topSuppliers = Purchase::select('supplier_id', DB::raw('SUM(total) as total_purchases'))->with('supplier')->whereHas('supplier')->groupBy('supplier_id')->orderByDesc('total_purchases')->limit(5)->get()->map(
            fn($item) => (object) [
                'id' => $item->supplier_id,
                'name' => $item->supplier->name ?? 'Unknown Supplier',
                'location' => $item->supplier->location ?? 'N/A',
                'total_purchases' => $item->total_purchases,
            ],
        );

        return [
            'totalSuppliers' => $totalSuppliers,
            'activeSuppliers' => $activeSuppliers,
            'supplierPaymentPerformance' => round($supplierPaymentPerformance, 1),
            'avgPurchaseValue' => $avgPurchaseValue,
            'totalOutstanding' => $totalOutstanding,
            'topSuppliers' => $topSuppliers,
        ];
    }

    private function getAverageDueDays()
    {
        $avgDays = Sales::where('status', 'Paid')
            ->whereNotNull('due_date')
            ->get()
            ->avg(function ($sale) {
                $latestPaymentDate = $sale->payments()->latest('payment_date')->value('payment_date');
                if ($latestPaymentDate && $sale->due_date) {
                    return Carbon::parse($latestPaymentDate)->diffInDays($sale->due_date);
                }
                return 0;
            });

        return round($avgDays) ?? 0;
    }

    private function getCollectionRate()
    {
        $totalInvoices = Sales::count();
        $paidInvoices = Sales::where('status', 'Paid')->count();

        return $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100) : 0;
    }

    private function getRecentTransactions($dates, $reportType = 'all')
    {
        $transactions = collect();

        if ($reportType === 'all' || $reportType === 'sales') {
            $sales = Sales::with('customer')
                ->whereBetween('order_date', [$dates['start'], $dates['end']])
                ->orderBy('order_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($sale) {
                    return [
                        'type' => 'sale',
                        'invoice' => $sale->invoice,
                        'customer_supplier' => $sale->customer->name ?? 'Walk-in Customer',
                        'date' => $sale->order_date,
                        'amount' => $sale->total,
                        'status' => $sale->status,
                    ];
                });

            $transactions = $transactions->merge($sales);
        }

        if ($reportType === 'all' || $reportType === 'purchases') {
            $purchases = Purchase::with('supplier')
                ->whereBetween('order_date', [$dates['start'], $dates['end']])
                ->orderBy('order_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($purchase) {
                    return [
                        'type' => 'Purchase',
                        'invoice' => $purchase->invoice,
                        'customer_supplier' => $purchase->supplier->name ?? 'Unknown Supplier',
                        'date' => $purchase->order_date,
                        'amount' => $purchase->total,
                        'status' => $purchase->status,
                    ];
                });

            $transactions = $transactions->merge($purchases);
        }

        return $transactions->sortByDesc('date')->take(20)->values();
    }
}
