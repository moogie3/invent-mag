<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    /**
     * Mobile dashboard KPI summary.
     *
     * Lightweight subset of the admin dashboard for the PWA home screen:
     * key metrics, revenue/sales totals, low-stock count, and recent
     * transactions. Reuses DashboardService so numbers match the web panel.
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @response scenario=success {
     *   "key_metrics": [{"title": "Monthly Earnings", "value": 1500000, "format": "currency"}],
     *   "total_revenue": 12500000,
     *   "monthly_sales": 3200000,
     *   "unpaid_revenue": 450000,
     *   "unpaid_liability": 210000,
     *   "low_stock_count": 4,
     *   "recent_sales": [{"invoice": "POS-0001", "total": 150000, "status": "Paid"}],
     *   "recent_purchases": [],
     *   "collection_rate": 92.5,
     *   "avg_due_days": 14
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', 'this_year');
        $dates = $this->dashboardService->calculateDateRange(
            $dateRange,
            $request->get('start_date'),
            $request->get('end_date'),
        );

        $data = $this->dashboardService->getDashboardData(
            $dates,
            $request->get('report_type', 'all'),
            $request->get('category_id'),
        );

        return response()->json([
            'key_metrics' => $data['keyMetrics'],
            'total_revenue' => (float) $data['totalRevenue'],
            'monthly_sales' => (float) $data['countSales'],
            'unpaid_revenue' => (float) $data['countRevenue'],
            'unpaid_liability' => (float) $data['countliability'],
            'low_stock_count' => (int) $data['lowStockCount'],
            'recent_sales' => $data['recentSales'],
            'recent_purchases' => $data['recentPurchases'],
            'collection_rate' => $data['collectionRate'],
            'avg_due_days' => $data['avgDueDays'],
        ]);
    }
}
