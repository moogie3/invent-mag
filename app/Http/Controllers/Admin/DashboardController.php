<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $reportType = $request->get('report_type', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $dateRange = $request->get('date_range', 'this_month');
        $categoryId = $request->get('category_id');

        $dates = $this->dashboardService->calculateDateRange($dateRange, $startDate, $endDate);

        if ($request->ajax()) {
            $period = $request->get('period', '30days');
            $type = $request->get('type', 'sales');
            $data = $this->dashboardService->getChartData($period, $type);
            return response()->json($data);
        }

        $data = $this->dashboardService->getDashboardData($dates, $reportType, $categoryId);

        return view('admin.dashboard', $data);
    }
}
