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
     * @param  string  $model  'linear' or 'holt-winters'
     */
    public function generateForecast(int $monthsToForecast = 6, string $model = 'holt-winters'): array
    {
        $monthFormat = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', order_date)"
            : "DATE_FORMAT(order_date, '%Y-%m')";

        // Get monthly sales data for the last 24 months for better accuracy
        $salesData = Sales::select(
            DB::raw($monthFormat.' as month'),
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

        $historicalValues = $salesData->pluck('total_sales')->map(fn ($val) => (float) $val)->toArray();
        $historicalLabels = $salesData->pluck('month')->toArray();

        if ($model === 'holt-winters' && $salesData->count() >= 12) {
            $optimizationResult = $this->optimizeHoltWinters($historicalValues);
            $forecastData = $this->forecastHoltWinters(
                $historicalValues,
                $monthsToForecast,
                12,
                $optimizationResult['alpha'],
                $optimizationResult['beta'],
                $optimizationResult['gamma']
            );
            $mape = $optimizationResult['mape'];
        } else {
            $forecastData = $this->forecastLinearRegression($historicalValues, $monthsToForecast);
            $metrics = $this->calculateLinearRegressionMetrics($historicalValues);
            $mape = $metrics['mape'];
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
            'metrics' => [
                'mape' => $mape,
            ],
        ];
    }

    private function calculateLinearRegressionMetrics(array $data): array
    {
        $x = range(1, count($data));
        $y = $data;
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumX2 = array_sum(array_map(fn ($val) => $val * $val, $x));
        $sumXY = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if ($denominator == 0) {
            return ['mape' => 0, 'rmse' => 0];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        $errorsSq = 0;
        $absPercentErrors = 0;
        $validMapeCount = 0;

        for ($i = 0; $i < $n; $i++) {
            $predicted = $slope * $x[$i] + $intercept;
            $actual = $y[$i];

            $errorsSq += pow($actual - $predicted, 2);

            if ($actual > 0) {
                $absPercentErrors += abs(($actual - $predicted) / $actual);
                $validMapeCount++;
            }
        }

        $rmse = sqrt($errorsSq / $n);
        $mape = $validMapeCount > 0 ? ($absPercentErrors / $validMapeCount) * 100 : 0;

        return ['mape' => round($mape, 2), 'rmse' => round($rmse, 2)];
    }

    private function optimizeHoltWinters(array $data, int $seasonLength = 12): array
    {
        $bestAlpha = 0.3;
        $bestBeta = 0.1;
        $bestGamma = 0.1;
        $bestRmse = PHP_FLOAT_MAX;
        $bestMape = 0;

        // Simple grid search over a reasonable parameter space
        // Step of 0.2 keeps it fast (125 combinations)
        $steps = [0.1, 0.3, 0.5, 0.7, 0.9];

        foreach ($steps as $alpha) {
            foreach ($steps as $beta) {
                foreach ($steps as $gamma) {
                    $metrics = $this->calculateHoltWintersInSampleMetrics($data, $seasonLength, $alpha, $beta, $gamma);

                    if ($metrics['rmse'] < $bestRmse) {
                        $bestRmse = $metrics['rmse'];
                        $bestMape = $metrics['mape'];
                        $bestAlpha = $alpha;
                        $bestBeta = $beta;
                        $bestGamma = $gamma;
                    }
                }
            }
        }

        return [
            'alpha' => $bestAlpha,
            'beta' => $bestBeta,
            'gamma' => $bestGamma,
            'rmse' => round($bestRmse, 2),
            'mape' => round($bestMape, 2),
        ];
    }

    private function calculateHoltWintersInSampleMetrics(array $data, int $seasonLength, float $alpha, float $beta, float $gamma): array
    {
        $level = 0;
        $trend = 0;
        $season = [];

        // Initialize seasonal components
        for ($i = 0; $i < $seasonLength; $i++) {
            $season[] = $data[$i] ?? 0;
        }

        $level = $data[0] ?? 0;
        $trend = (($data[$seasonLength - 1] ?? 0) - ($data[0] ?? 0)) / $seasonLength;

        $errorsSq = 0;
        $absPercentErrors = 0;
        $validMapeCount = 0;
        $n = count($data);

        foreach ($data as $i => $actual) {
            $seasonIndex = $i % $seasonLength;

            // In-sample prediction for current period (using previous level, trend, and season)
            $predicted = max(0, $level + $trend + $season[$seasonIndex]);

            // Track error metrics
            $errorsSq += pow($actual - $predicted, 2);
            if ($actual > 0) {
                $absPercentErrors += abs(($actual - $predicted) / $actual);
                $validMapeCount++;
            }

            // Update components for next period
            $lastLevel = $level;
            $lastTrend = $trend;

            $level = $alpha * ($actual - $season[$seasonIndex]) + (1 - $alpha) * ($lastLevel + $lastTrend);
            $trend = $beta * ($level - $lastLevel) + (1 - $beta) * $lastTrend;
            $season[$seasonIndex] = $gamma * ($actual - $level) + (1 - $gamma) * $season[$seasonIndex];
        }

        $rmse = sqrt($errorsSq / max(1, $n));
        $mape = $validMapeCount > 0 ? ($absPercentErrors / $validMapeCount) * 100 : 0;

        return ['rmse' => $rmse, 'mape' => $mape];
    }

    private function forecastLinearRegression(array $data, int $monthsToForecast): array
    {
        $x = range(1, count($data));
        $y = $data;

        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumX2 = array_sum(array_map(fn ($val) => $val * $val, $x));
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
