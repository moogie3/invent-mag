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
            $type = $request->get('type', 'sales');

            // Defer heavy sales forecasting to an AJAX call after page load
            if ($request->get('action') === 'forecast') {
                $tenant = app('currentTenant');
                if (!$tenant || !$tenant->hasFeature('sales_forecasting')) {
                    return response()->json(['labels' => [], 'historical' => [], 'forecast' => [], 'metrics' => null]);
                }

                $forecastData = $this->salesForecastService->generateForecast();

                if (!empty($forecastData['labels'])) {
                    $historicalCount = count($forecastData['historical']);
                    $labelsCount = count($forecastData['labels']);

                    $historicalSeries = array_pad($forecastData['historical'], $labelsCount, null);
                    $forecastSeries = array_merge(array_fill(0, $historicalCount, null), $forecastData['forecast']);

                    return response()->json([
                        'labels' => $forecastData['labels'],
                        'historical' => $historicalSeries,
                        'forecast' => $forecastSeries,
                        'metrics' => $forecastData['metrics'] ?? null
                    ]);
                }
                
                return response()->json(['labels' => [], 'historical' => [], 'forecast' => [], 'metrics' => null]);
            }

            // If the caller passes date_range (same parameter as the full page load),
            // use the monthly aggregation so the refreshed chart matches the initial view.
            if ($request->has('date_range')) {
                $ajaxDateRange = $request->get('date_range', 'this_year');
                $ajaxStart     = $request->get('start_date');
                $ajaxEnd       = $request->get('end_date');
                $data = $this->dashboardService->getChartDataByDateRange($ajaxDateRange, $type, $ajaxStart, $ajaxEnd);
            } else {
                // Legacy path: rolling period (7days / 30days / 3months / year) → daily granularity
                $period = $request->get('period', '30days');
                $data   = $this->dashboardService->getChartData($period, $type);
            }

            return response()->json($data);
        }

        $data = $this->dashboardService->getDashboardData($dates, $reportType, $categoryId);

        // Sales Forecast is deferred via AJAX, so pass a dummy initial structure to trigger the loader
        $tenant = app('currentTenant');
        if ($tenant && $tenant->hasFeature('sales_forecasting')) {
            $data['salesForecast'] = [
                'labels' => [],
                'historical' => [],
                'forecast' => [],
                'deferred' => true
            ];
        } else {
            $data['salesForecast'] = null;
        }

        return view('admin.dashboard', $data);
    }
}
