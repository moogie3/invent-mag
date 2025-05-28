<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Sales;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $dateRange = $request->get('date_range', 'this_month');
        $reportType = $request->get('report_type', 'all');
        $categoryId = $request->get('category_id');
        $paymentStatus = $request->get('payment_status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Handle AJAX requests for chart updates
        if ($request->ajax()) {
            $period = $request->get('period', '30days');
            $type = $request->get('type', 'sales');

            $data = $this->getChartData($type, $period);

            return response()->json([
                'labels' => $data['labels'],
                'data' => $data['data'],
                'formatted' => collect($data['data'])->map(fn($val) => \App\Helpers\CurrencyHelper::format($val))
            ]);
        }

        // Calculate date range
        $dates = $this->calculateDateRange($dateRange, $startDate, $endDate);

        // Get summary statistics
        $totalRevenue = $this->getTotalRevenue($dates, $categoryId, $paymentStatus);
        $totalSalesCount = $this->getTotalSalesCount($dates, $categoryId, $paymentStatus);
        $totalPurchases = $this->getTotalPurchases($dates, $categoryId);

        // Get monthly data for revenue vs expenses table
        $monthlyData = $this->getMonthlyData($dates, $categoryId);

        // Get product performance
        $productPerformance = $this->getProductPerformance($dates, $categoryId);
        $maxRevenue = $productPerformance->max('revenue') ?? 1;

        // Get payment status counts
        $paymentCounts = $this->getPaymentStatusCounts($dates, $categoryId);

        // Get top categories
        $topCategories = $this->getTopCategories($dates);

        // Get customer analytics
        $customerAnalytics = $this->getCustomerAnalytics($dates);

        // Get supplier analytics
        $supplierAnalytics = $this->getSupplierAnalytics($dates);

        // Get recent transactions
        $recentTransactions = $this->getRecentTransactions($dates, $reportType);

        // Sales chart data
        $salesData = Sales::selectRaw('
            DATE_FORMAT(order_date, "%b") as month,
            MONTH(order_date) as month_num,
            SUM(total) as total
        ')
        ->whereYear('order_date', now()->year)
        ->groupBy(DB::raw('MONTH(order_date)'), DB::raw('DATE_FORMAT(order_date, "%b")'))
        ->orderBy('month_num')
        ->get();

        $chartLabels = $salesData->pluck('month')->toArray();
        $chartData = $salesData->pluck('total')->toArray();

        // Purchase chart data
        $purchaseData = Purchase::selectRaw('
            DATE_FORMAT(order_date, "%b") as month,
            MONTH(order_date) as month_num,
            SUM(total) as total
        ')
        ->whereYear('order_date', now()->year)
        ->groupBy(DB::raw('MONTH(order_date)'), DB::raw('DATE_FORMAT(order_date, "%b")'))
        ->orderBy('month_num')
        ->get();

        $purchaseChartLabels = $purchaseData->pluck('month')->toArray();
        $purchaseChartData = $purchaseData->pluck('total')->toArray();

        // Get all categories for filter
        $categories = Categories::orderBy('name')->get();

        return view('admin.reports', compact(
            'chartLabels',
            'chartData',
            'purchaseChartLabels',
            'purchaseChartData',
            'totalRevenue',
            'totalSalesCount',
            'totalPurchases',
            'monthlyData',
            'productPerformance',
            'maxRevenue',
            'topCategories',
            'recentTransactions',
            'categories'
        ))->with($paymentCounts)->with($customerAnalytics)->with($supplierAnalytics);
    }

    private function calculateDateRange($dateRange, $startDate = null, $endDate = null)
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

    private function getTotalSalesCount($dates, $categoryId = null, $paymentStatus = null)
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

        return $query->count();
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

    // OPTION 1: Explicit column selection (Recommended) - Fixed for existing columns only
    private function getProductPerformance($dates, $categoryId = null)
    {
        $query = Product::select(['products.id', 'products.name', 'products.code', 'products.category_id', 'products.image', 'products.price', 'products.stock_quantity', 'products.description', 'products.created_at', 'products.updated_at', DB::raw('COALESCE(SUM(sales_items.quantity), 0) as units_sold'), DB::raw('COALESCE(SUM(sales_items.quantity * sales_items.customer_price), 0) as revenue')])
            ->leftJoin('sales_items', 'products.id', '=', 'sales_items.product_id')
            ->leftJoin('sales', 'sales_items.sales_id', '=', 'sales.id')
            ->whereBetween('sales.order_date', [$dates['start'], $dates['end']])
            ->with('category')
            ->groupBy(['products.id', 'products.name', 'products.code', 'products.category_id', 'products.image', 'products.price', 'products.stock_quantity', 'products.description', 'products.created_at', 'products.updated_at']);

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        return $query->orderBy('revenue', 'desc')->limit(10)->get();
    }

    private function getPaymentStatusCounts($dates, $categoryId = null)
    {
        $query = Sales::whereBetween('order_date', [$dates['start'], $dates['end']]);

        if ($categoryId) {
            $query->whereHas('items.product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $counts = $query->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status')->toArray();

        return [
            'paidCount' => $counts['Paid'] ?? 0,
            'pendingCount' => $counts['Pending'] ?? 0,
            'overdueCount' => $counts['Overdue'] ?? 0,
            'partialCount' => $counts['Partial'] ?? 0,
            'unpaidCount' => $counts['Unpaid'] ?? 0,
        ];
    }

    private function getTopCategories($dates)
{
    // First get category revenue data
    $categoryRevenue = DB::table('sales_items')
        ->join('sales', 'sales_items.sales_id', '=', 'sales.id')
        ->join('products', 'sales_items.product_id', '=', 'products.id')
        ->select([
            'products.category_id',
            DB::raw('COUNT(DISTINCT products.id) as products_count'),
            DB::raw('SUM(sales_items.quantity * sales_items.customer_price) as revenue')
        ])
        ->whereBetween('sales.order_date', [$dates['start'], $dates['end']])
        ->groupBy('products.category_id')
        ->orderBy('revenue', 'desc')
        ->limit(5)
        ->get()
        ->keyBy('category_id');

    // Get categories and merge with revenue data
    $categoryIds = $categoryRevenue->pluck('category_id');
    $categories = Categories::whereIn('id', $categoryIds)->get();

    $totalRevenue = $this->getTotalRevenue($dates);

    return $categories->map(function ($category) use ($categoryRevenue, $totalRevenue) {
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
    })->sortByDesc('revenue')->values();
}

    private function getCustomerAnalytics($dates)
    {
        $totalCustomers = Customer::count();

        $activeCustomers = Customer::whereHas('sales', function ($query) use ($dates) {
            $query->whereBetween('order_date', [$dates['start'], $dates['end']]);
        })->count();

        // Calculate retention rate (customers who made purchases in both current and previous period)
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

        // Average order value
        $avgOrderValue = Sales::whereBetween('order_date', [$dates['start'], $dates['end']])->avg('total') ?? 0;

        // Customer lifetime value (simplified calculation)
        $customerLifetimeValue = $totalCustomers > 0 ? Sales::sum('total') / $totalCustomers : 0;

        return [
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'retentionRate' => round($retentionRate, 1),
            'avgOrderValue' => $avgOrderValue,
            'customerLifetimeValue' => $customerLifetimeValue,
        ];
    }

    private function getSupplierAnalytics($dates)
    {
        $totalSuppliers = Supplier::count();

        $activeSuppliers = Supplier::whereHas('purchases', function ($query) use ($dates) {
            $query->whereBetween('order_date', [$dates['start'], $dates['end']]);
        })->count();

        // Payment performance (percentage of paid purchases)
        $totalPurchases = Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])->count();
        $paidPurchases = Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])
            ->where('status', 'Paid')
            ->count();

        $supplierPaymentPerformance = $totalPurchases > 0 ? ($paidPurchases / $totalPurchases) * 100 : 0;

        // Average purchase value
        $avgPurchaseValue = Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])->avg('total') ?? 0;

        // Total outstanding (unpaid + partial)
        $totalOutstanding =
            Purchase::whereBetween('order_date', [$dates['start'], $dates['end']])
                ->whereIn('status', ['Unpaid', 'Partial'])
                ->sum('total') ?? 0;

        return [
            'totalSuppliers' => $totalSuppliers,
            'activeSuppliers' => $activeSuppliers,
            'supplierPaymentPerformance' => round($supplierPaymentPerformance, 1),
            'avgPurchaseValue' => $avgPurchaseValue,
            'totalOutstanding' => $totalOutstanding,
        ];
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

    private function getChartData($type, $period)
{
    $query = null;
    $dateColumn = 'created_at';

    // Determine date range
    $startDate = match($period) {
        '7days' => now()->subDays(7),
        '30days' => now()->subDays(30),
        '3months' => now()->subMonths(3),
        'year' => now()->subYear(),
        default => now()->subDays(30)
    };

    if ($type === 'sales') {
        $query = Sales::where('created_at', '>=', $startDate);
    } else {
        $query = Purchase::where('created_at', '>=', $startDate);
    }

    // Group by appropriate period
    $groupBy = in_array($period, ['7days', '30days']) ? 'DATE(created_at)' : 'DATE_FORMAT(created_at, "%Y-%m")';

    $results = $query->selectRaw("$groupBy as period, SUM(total_amount) as total")
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();

    $labels = [];
    $data = [];

    foreach ($results as $result) {
        $labels[] = in_array($period, ['7days', '30days'])
            ? Carbon::parse($result->period)->format('M d')
            : Carbon::parse($result->period . '-01')->format('M Y');
        $data[] = (float) $result->total;
    }

    return compact('labels', 'data');
}

    public function export(Request $request)
    {
        $format = $request->get('format', 'pdf');

        // Get the same data as index
        $data = $this->index($request);

        switch ($format) {
            case 'pdf':
                return $this->exportToPDF($data);
            case 'csv':
                return $this->exportToCSV($data);
            default:
                return redirect()->back()->with('error', 'Invalid export format');
        }
    }

    private function exportToPDF($data)
    {
        // Implement PDF export logic here
        // You can use libraries like DomPDF or TCPDF

        return response()->json(['message' => 'PDF export functionality to be implemented']);
    }

    private function exportToCSV($data)
    {
        // Implement CSV export logic here

        return response()->json(['message' => 'CSV export functionality to be implemented']);
    }
}
