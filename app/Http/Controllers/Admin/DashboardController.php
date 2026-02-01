<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\SalesForecastService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $salesForecastService;

    public function __construct(
        DashboardService $dashboardService,
        SalesForecastService $salesForecastService
    ) {
        $this->dashboardService = $dashboardService;
        $this->salesForecastService = $salesForecastService;
    }

    public function index(Request $request)
    {
        $reportType = $request->get('report_type', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        // Default to 'this_year' to ensure data visibility especially at the start of a month
        $dateRange = $request->get('date_range', 'this_year');
        $categoryId = $request->get('category_id');

        $dates = $this->dashboardService->calculateDateRange($dateRange, $startDate, $endDate);

        if ($request->ajax()) {
            $period = $request->get('period', '30days');
            $type = $request->get('type', 'sales');
            $data = $this->dashboardService->getChartData($period, $type);
            return response()->json($data);
        }

        $data = $this->dashboardService->getDashboardData($dates, $reportType, $categoryId);

        $forecastData = $this->salesForecastService->generateForecast();

        if (!empty($forecastData['labels'])) {
            $historicalCount = count($forecastData['historical']);
            $labelsCount = count($forecastData['labels']);

            $historicalSeries = array_pad($forecastData['historical'], $labelsCount, null);
            $forecastSeries = array_merge(array_fill(0, $historicalCount, null), $forecastData['forecast']);

            $data['salesForecast'] = [
                'labels' => $forecastData['labels'],
                'historical' => $historicalSeries,
                'forecast' => $forecastSeries,
            ];
        } else {
            $data['salesForecast'] = [
                'labels' => [],
                'historical' => [],
                'forecast' => [],
            ];
        }

        return view('admin.dashboard', $data);
    }
}
