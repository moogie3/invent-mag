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
        $recentSales = Sales::with('customer')->orderBy('created_at', 'desc')->limit(3)->get();
        $recentPurchases = Purchase::with('supplier')->orderBy('created_at', 'desc')->limit(3)->get();
        
        // New aging calculations
        $arAging = $this->getAccountsReceivableAging();
        $apAging = $this->getAccountsPayableAging();

        $keyMetrics = $this->formatKeyMetrics($totalLiability, $unpaidLiability, $totalRevenue, $unpaidRevenue, $monthlySales, $outCountUnpaid, $inCountUnpaid);
        $financialItems = $this->prepareFinancialItems($totalLiability, $unpaidLiability, $totalRevenue);
        $invoiceStatusData = $this->prepareInvoiceStatusData($arAging, $apAging); // Pass aging data here
        $customerInsights = $this->prepareCustomerInsights();
        $customerAnalytics = $this->getCustomerAnalytics($dates);
        $supplierAnalytics = $this->getSupplierAnalytics($dates);
        $recentTransactions = $this->getRecentTransactions($dates, $reportType);
        $topCategories = $this->getTopCategories($dates);
        $monthlyData = $this->getMonthlyData($dates, $categoryId);
        $lowStockProducts = Product::getLowStockProducts();
        $expiringSoonItems = \App\Models\POItem::getExpiringSoonItems();

        $monthFormat = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%b', order_date)" : "DATE_FORMAT(order_date, '%b')";
        $monthNumFormat = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%m', order_date)" : "MONTH(order_date)";

        $salesData = Sales::selectRaw("
            $monthFormat as month,
            $monthNumFormat as month_num,
            SUM(total) as total
        ")
            ->whereYear('order_date', now()->year)
            ->groupBy(DB::raw($monthNumFormat), DB::raw($monthFormat))
            ->orderBy('month_num')
            ->get();

        $purchaseData = Purchase::selectRaw("
            $monthFormat as month,
            $monthNumFormat as month_num,
            SUM(total) as total
        ")
            ->whereYear('order_date', now()->year)
            ->groupBy(DB::raw($monthNumFormat), DB::raw($monthFormat))
            ->orderBy('month_num')
            ->get();

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
            'topSellingProducts' => $this->getTopSellingProducts(),
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'lowStockCount' => $lowStockProducts->count(),
            'lowStockProducts' => $lowStockProducts,
            'expiringSoonItems' => $expiringSoonItems,
            'totalliability' => $totalLiability,
            'countliability' => $unpaidLiability,
            'paidDebtMonthly' => $this->getPaidDebtMonthly(),
            'countRevenue' => $unpaidRevenue,
            'countSales' => $monthlySales,
            'liabilitypaymentMonthly' => $this->getLiabilityPaymentsMonthly(),
            'inCountUnpaid' => $inCountUnpaid,
            'outCountUnpaid' => $outCountUnpaid,
            'totalRevenue' => $totalRevenue,
            'avgDueDays' => $this->getAverageDueDays(),
            'collectionRate' => $this->getCollectionRate(),
            'keyMetrics' => $keyMetrics,
            'financialItems' => $financialItems,
            'invoiceStatusData' => $invoiceStatusData,
            'customerInsights' => $customerInsights,
            'arAging' => $arAging, // Add AR aging to the dashboard data
            'apAging' => $apAging, // Add AP aging to the dashboard data
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

    private function getAccountsReceivableAging()
    {
        $now = Carbon::now();
        $aging = [
            'current' => 0,
            '1-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
            'total_overdue' => 0,
        ];

        $unpaidSales = Sales::whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get();

        foreach ($unpaidSales as $sale) {
            $daysOverdue = $now->diffInDays($sale->due_date, false); // false for absolute difference

            if ($daysOverdue >= 0) { // Due date is today or in the future
                $aging['current'] += $sale->total;
            } elseif ($daysOverdue >= -30) { // 1-30 days overdue
                $aging['1-30'] += $sale->total;
                $aging['total_overdue'] += $sale->total;
            } elseif ($daysOverdue >= -60) { // 31-60 days overdue
                $aging['31-60'] += $sale->total;
                $aging['total_overdue'] += $sale->total;
            } elseif ($daysOverdue >= -90) { // 61-90 days overdue
                $aging['61-90'] += $sale->total;
                $aging['total_overdue'] += $sale->total;
            } else { // 90+ days overdue
                $aging['90+'] += $sale->total;
                $aging['total_overdue'] += $sale->total;
            }
        }
        return $aging;
    }

    private function getAccountsPayableAging()
    {
        $now = Carbon::now();
        $aging = [
            'current' => 0,
            '1-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
            'total_overdue' => 0,
        ];

        $unpaidPurchases = Purchase::whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get();

        foreach ($unpaidPurchases as $purchase) {
            $daysOverdue = $now->diffInDays($purchase->due_date, false);

            if ($daysOverdue >= 0) { // Due date is today or in the future
                $aging['current'] += $purchase->total;
            } elseif ($daysOverdue >= -30) { // 1-30 days overdue
                $aging['1-30'] += $purchase->total;
                $aging['total_overdue'] += $purchase->total;
            } elseif ($daysOverdue >= -60) { // 31-60 days overdue
                $aging['31-60'] += $purchase->total;
                $aging['total_overdue'] += $purchase->total;
            } elseif ($daysOverdue >= -90) { // 61-90 days overdue
                $aging['61-90'] += $purchase->total;
                $aging['total_overdue'] += $purchase->total;
            } else { // 90+ days overdue
                $aging['90+'] += $purchase->total;
                $aging['total_overdue'] += $purchase->total;
            }
        }
        return $aging;
    }

    private function prepareFinancialItems($totalLiability, $unpaidLiability, $totalRevenue)
    {
        $operatingExpenses = $totalLiability - $unpaidLiability;

        // Previous month
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $prevMonthPaidLiabilities = Purchase::whereBetween('updated_at', [$lastMonthStart, $lastMonthEnd])->where('status', 'Paid')->sum('total');
        $prevMonthReceivablePaid = Sales::whereBetween('updated_at', [$lastMonthStart, $lastMonthEnd])->where('status', 'Paid')->sum('total');
        $prevMonthTotalLiability = Purchase::where('order_date', '<=', $lastMonthEnd)->sum('total');
        $prevMonthUnpaidLiability = Purchase::where('order_date', '<=', $lastMonthEnd)->where('status', 'Unpaid')->sum('total');
        $prevMonthOperatingExpenses = $prevMonthTotalLiability - $prevMonthUnpaidLiability;
        $prevMonthTotalRevenue = Sales::whereBetween('order_date', [$lastMonthStart, $lastMonthEnd])->sum('total');
        $prevNetProfit = $prevMonthTotalRevenue - $prevMonthOperatingExpenses;

        // New metrics
        $cogs = $this->getTotalPurchases(['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()]); // Cost of Goods Sold for current month
        $grossProfit = $totalRevenue - $cogs;
        $operatingIncome = $grossProfit - $operatingExpenses;

        // Previous month for new metrics
        $prevMonthCogs = $this->getTotalPurchases(['start' => $lastMonthStart, 'end' => $lastMonthEnd]);
        $prevGrossProfit = $prevMonthTotalRevenue - $prevMonthCogs;
        $prevOperatingIncome = $prevGrossProfit - $prevMonthOperatingExpenses;


        $paidLiabilities = $this->getLiabilityPaymentsMonthly();
        $receivablePaid = $this->getPaidDebtMonthly();
        $netProfit = $totalRevenue - $operatingExpenses;

        $paidLiabilitiesChange = $prevMonthPaidLiabilities > 0 ? (($paidLiabilities - $prevMonthPaidLiabilities) / $prevMonthPaidLiabilities) * 100 : 0;
        $receivablePaidChange = $prevMonthReceivablePaid > 0 ? (($receivablePaid - $prevMonthReceivablePaid) / $prevMonthReceivablePaid) * 100 : 0;
        $operatingExpensesChange = $prevMonthOperatingExpenses > 0 ? (($operatingExpenses - $prevMonthOperatingExpenses) / $prevMonthOperatingExpenses) * 100 : 0;
        $netProfitChange = $prevNetProfit > 0 ? (($netProfit - $prevNetProfit) / $prevNetProfit) * 100 : 0;
        $grossProfitChange = $prevGrossProfit > 0 ? (($grossProfit - $prevGrossProfit) / $prevGrossProfit) * 100 : 0;
        $operatingIncomeChange = $prevOperatingIncome > 0 ? (($operatingIncome - $prevOperatingIncome) / $prevOperatingIncome) * 100 : 0;


        return [
            [
                'label' => __('messages.total_liabilities'),
                'value' => $totalLiability,
                'icon' => 'ti-receipt-2',
                'change' => 0,
            ],
            [
                'label' => __('messages.this_month_paid_liabilities'),
                'value' => $paidLiabilities,
                'icon' => 'ti-calendar-check',
                'change' => $paidLiabilitiesChange,
            ],
            [
                'label' => __('messages.total_account_receivable'),
                'value' => $totalRevenue,
                'icon' => 'ti-file-invoice',
                'change' => 0,
            ],
            [
                'label' => __('messages.this_month_receivable_paid'),
                'value' => $receivablePaid,
                'icon' => 'ti-cash',
                'change' => $receivablePaidChange,
            ],
            [
                'label' => __('messages.operating_expenses'),
                'value' => $operatingExpenses,
                'icon' => 'ti-shopping-cart-cog',
                'change' => $operatingExpensesChange,
            ],
            [
                'label' => __('messages.net_profit'),
                'value' => $netProfit,
                'icon' => 'ti-chart-infographic',
                'change' => $netProfitChange,
            ],
            [
                'label' => __('messages.gross_profit'),
                'value' => $grossProfit,
                'icon' => 'ti-moneybag',
                'change' => $grossProfitChange,
            ],
            [
                'label' => __('messages.operating_income'),
                'value' => $operatingIncome,
                'icon' => 'ti-building-bank',
                'change' => $operatingIncomeChange,
            ],
        ];
    }

    private function prepareInvoiceStatusData(array $arAging, array $apAging)
    {
        // Recalculate counts based on aging data
        $outCount = Sales::count(); // Total sales invoices
        $inCount = Purchase::count(); // Total purchase invoices

        $outCountUnpaid = $arAging['current'] + $arAging['total_overdue']; // Unpaid AR
        $inCountUnpaid = $apAging['current'] + $apAging['total_overdue']; // Unpaid AP

        $totalInvoices = $outCount + $inCount;
        $collectionRate = $this->getCollectionRate();
        $avgDueDays = $this->getAverageDueDays();

        $outPercentage = ($outCount > 0) ? (($outCount - $outCountUnpaid) / $outCount) * 100 : 0;
        $inPercentage = ($inCount > 0) ? (($inCount - $inCountUnpaid) / $inCount) * 100 : 0;

        return [
            'totalInvoices' => $totalInvoices,
            'collectionRate' => $collectionRate,
            'collectionRateDisplay' => round($collectionRate),
            'outCount' => $outCount,
            'inCount' => $inCount,
            'outCountUnpaid' => $outCountUnpaid,
            'inCountUnpaid' => $inCountUnpaid,
            'outPercentage' => $outPercentage,
            'inPercentage' => $inPercentage,
            'avgDueDays' => $avgDueDays,
            'arAging' => $arAging, // Include AR aging details
            'apAging' => $apAging, // Include AP aging details
        ];
    }

    private function prepareCustomerInsights()
    {
        $dateDiffRaw = DB::connection()->getDriverName() === 'sqlite'
            ? 'julianday(payments.payment_date) - julianday(sales.due_date)'
            : 'DATEDIFF(payments.payment_date, sales.due_date)';

        $avgDueDays = Sales::where('sales.status', 'Paid')
            ->whereNotNull('sales.due_date')
            ->join('payments', function ($join) {
                $join->on('sales.id', '=', 'payments.paymentable_id')
                    ->where('payments.paymentable_type', Sales::class);
            })
            ->select(DB::raw("AVG($dateDiffRaw) as avg_days"))
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
