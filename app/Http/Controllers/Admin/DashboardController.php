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
        // Get recent activities in the format expected by the view
        $recentSales = Sales::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        $recentPurchases = Purchase::with('supplier')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return view('admin.dashboard', [
            'topSellingProducts' => $this->getTopSellingProducts(),
            'topCustomers' => $this->getTopCustomers(),
            'topSuppliers' => $this->getTopSuppliers(),
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'lowStockCount' => $this->getLowStockCount(),
            'expiringSoonCount' => $this->getExpiringSoonCount(),
            'totalliability' => Purchase::sum('total'),
            'countliability' => Purchase::where('status', 'Unpaid')->sum('total'),
            'paidDebtMonthly' => $this->getPaidDebtMonthly(),
            'countRevenue' => Sales::where('status', 'Unpaid')->sum('total'),
            'countSales' => Sales::where('status', 'Paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),
            'liabilitypaymentMonthly' => $this->getLiabilityPaymentsMonthly(),
            'inCount' => $this->getPurchaseCountByLocation('IN'),
            'outCount' => $this->getPurchaseCountByLocation('OUT'),
            'inCountUnpaid' => $this->getPurchaseCountByLocation('IN', 'Unpaid'),
            'outCountUnpaid' => $this->getPurchaseCountByLocation('OUT', 'Unpaid'),
            'totalRevenue' => $this->getMonthlyRevenue(),
            'avgDueDays' => $this->getAverageDueDays(),
            'collectionRate' => $this->getCollectionRate(),
        ]);
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

    // This method is kept for completeness but not directly used in the adjusted view
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