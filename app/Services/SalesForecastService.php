<?php

namespace App\Services;

use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesForecastService
{
    /**
     * Generate a sales forecast.
     *
     * @param int $monthsToForecast
     * @param string $model 'linear' or 'holt-winters'
     * @return array
     */
    public function generateForecast(int $monthsToForecast = 6, string $model = 'holt-winters'): array
    {
        $monthFormat = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', order_date)"
            : "DATE_FORMAT(order_date, '%Y-%m')";

        // Get monthly sales data for the last 24 months for better accuracy
        $salesData = Sales::select(
            DB::raw($monthFormat . ' as month'),
            DB::raw('SUM(total) as total_sales')
        )
            ->where('order_date', '>=', Carbon::now()->subMonths(24)->startOfMonth())
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

        $historicalValues = $salesData->pluck('total_sales')->map(fn($val) => (float) $val)->toArray();
        $historicalLabels = $salesData->pluck('month')->toArray();

        if ($model === 'holt-winters' && $salesData->count() >= 12) {
            $forecastData = $this->forecastHoltWinters($historicalValues, $monthsToForecast);
        } else {
            $forecastData = $this->forecastLinearRegression($historicalValues, $monthsToForecast);
        }

        $lastMonth = Carbon::createFromFormat('Y-m', $salesData->last()->month);
        $forecastLabels = [];
        for ($i = 1; $i <= $monthsToForecast; $i++) {
            $forecastLabels[] = $lastMonth->copy()->addMonths($i)->format('Y-m');
        }

        return [
            'labels' => array_merge($historicalLabels, $forecastLabels),
            'historical' => $historicalValues,
            'forecast' => $forecastData,
        ];
    }

    private function forecastLinearRegression(array $data, int $monthsToForecast): array
    {
        $x = range(1, count($data));
        $y = $data;

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

        $forecast = [];
        for ($i = 1; $i <= $monthsToForecast; $i++) {
            $forecastX = $n + $i;
            $forecastY = $slope * $forecastX + $intercept;
            $forecast[] = max(0, $forecastY);
        }

        return $forecast;
    }

    private function forecastHoltWinters(array $data, int $forecastCount = 1, int $seasonLength = 12, float $alpha = 0.3, float $beta = 0.1, float $gamma = 0.1): array
    {
        $level = 0;
        $trend = 0;
        $season = [];
        $forecast = [];

        // Initialize seasonal components
        for ($i = 0; $i < $seasonLength; $i++) {
            $season[] = $data[$i] ?? 0;
        }

        // Initialize level and trend
        if (count($data) >= $seasonLength) {
            $level = $data[0];
            $trend = ($data[$seasonLength - 1] - $data[0]) / $seasonLength;
        } else {
            return [];
        }

        foreach ($data as $i => $value) {
            $lastLevel = $level;
            $lastTrend = $trend;
            $seasonIndex = $i % $seasonLength;

            $level = $alpha * ($value - $season[$seasonIndex]) + (1 - $alpha) * ($lastLevel + $lastTrend);
            $trend = $beta * ($level - $lastLevel) + (1 - $beta) * $lastTrend;
            $season[$seasonIndex] = $gamma * ($value - $level) + (1 - $gamma) * $season[$seasonIndex];
        }

        for ($i = 0; $i < $forecastCount; $i++) {
            $seasonIndex = (count($data) + $i) % $seasonLength;
            $forecastValue = $level + ($i + 1) * $trend + $season[$seasonIndex];
            $forecast[] = max(0, $forecastValue);
        }

        return $forecast;
    }
}
