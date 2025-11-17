<?php

namespace App\Services;

use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesForecastService
{
    /**
     * Generate a sales forecast using simple linear regression.
     *
     * @param int $monthsToForecast
     * @return array
     */
    public function generateForecast(int $monthsToForecast = 6): array
    {
        $monthFormat = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', order_date)"
            : "DATE_FORMAT(order_date, '%Y-%m')";

        // Get monthly sales data for the last 12 months
        $salesData = Sales::select(
            DB::raw($monthFormat . ' as month'),
            DB::raw('SUM(total) as total_sales')
        )
            ->where('order_date', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        if ($salesData->count() < 2) {
            return [
                'labels' => [],
                'historical' => [],
                'forecast' => [],
            ];
        }

        $x = []; // Months (as integers)
        $y = []; // Sales totals

        foreach ($salesData as $index => $data) {
            $x[] = $index + 1;
            $y[] = (float) $data->total_sales;
        }

        // Calculate linear regression parameters
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumX2 = array_sum(array_map(fn($val) => $val * $val, $x));
        $sumXY = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Generate forecast
        $forecastData = [];
        $lastMonth = Carbon::createFromFormat('Y-m', $salesData->last()->month);

        for ($i = 1; $i <= $monthsToForecast; $i++) {
            $forecastX = $n + $i;
            $forecastY = $slope * $forecastX + $intercept;
            $forecastData[] = max(0, $forecastY); // Sales can't be negative
        }

        // Prepare labels for the chart
        $historicalLabels = $salesData->pluck('month')->toArray();
        $forecastLabels = [];
        for ($i = 1; $i <= $monthsToForecast; $i++) {
            $forecastLabels[] = $lastMonth->copy()->addMonths($i)->format('Y-m');
        }

        return [
            'labels' => array_merge($historicalLabels, $forecastLabels),
            'historical' => $y,
            'forecast' => $forecastData,
        ];
    }
}
