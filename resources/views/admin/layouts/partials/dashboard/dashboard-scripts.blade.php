@php
    $dashboardConfig = [
        'currencySettings' => \App\Helpers\CurrencyHelper::getSettings(),
        'chartData' => [
            'chartLabels' => $chartLabels ?? [],
            'chartData' => $chartData ?? [],
            'chartDataFormatted' => collect($chartData ?? [])->map(fn($val) => \App\Helpers\CurrencyHelper::format($val)),
            'purchaseChartLabels' => $purchaseChartLabels ?? [],
            'purchaseChartData' => $purchaseChartData ?? [],
            'purchaseChartDataFormatted' => collect($purchaseChartData ?? [])->map(fn($val) => \App\Helpers\CurrencyHelper::format($val)),
            'salesForecast' => isset($salesForecast) ? $salesForecast : null,
        ],
        'routes' => [
            'dashboard' => route('admin.dashboard')
        ],
        'lang' => [
            'no_forecast' => __('messages.no_forecast_data'),
            'forecast_desc' => __('messages.forecast_description'),
            'historical' => __('messages.historical_sales'),
            'forecasted' => __('messages.forecasted_sales'),
        ]
    ];
@endphp
<div id="dashboard-data" data-config="{{ json_encode($dashboardConfig) }}" class="d-none"></div>
@vite('resources/js/admin/dashboard.js')