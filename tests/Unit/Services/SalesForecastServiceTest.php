<?php

namespace Tests\Unit\Services;

use App\Models\Sales;
use App\Services\SalesForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class SalesForecastServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SalesForecastService $salesForecastService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salesForecastService = new SalesForecastService();
    }

    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SalesForecastService::class, $this->salesForecastService);
    }


    public function it_returns_empty_array_when_not_enough_data()
    {
        // Only one month of data
        Sales::factory()->create(['order_date' => Carbon::now()->subMonths(1), 'total' => 1000]);

        $result = $this->salesForecastService->generateForecast();

        $this->assertIsArray($result);
        $this->assertEmpty($result['labels']);
        $this->assertEmpty($result['historical']);
        $this->assertEmpty($result['forecast']);
    }


    public function it_returns_correct_structure_with_enough_data_for_linear_regression()
    {
        // Create 6 months of sales data
        for ($i = 5; $i >= 0; $i--) {
            Sales::factory()->create([
                'order_date' => Carbon::now()->subMonths($i),
                'total' => 1000 * (6 - $i) // Create a simple linear trend
            ]);
        }

        $monthsToForecast = 3;
        $result = $this->salesForecastService->generateForecast($monthsToForecast, 'linear');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('historical', $result);
        $this->assertArrayHasKey('forecast', $result);
        $this->assertCount(6, $result['historical']);
        $this->assertCount($monthsToForecast, $result['forecast']);
        $this->assertCount(6 + $monthsToForecast, $result['labels']);
    }


    public function it_returns_correct_structure_with_enough_data_for_holt_winters()
    {
        // Create 14 months of sales data for Holt-Winters
        for ($i = 13; $i >= 0; $i--) {
            Sales::factory()->create([
                'order_date' => Carbon::now()->subMonths($i),
                'total' => 1000 + ($i % 6 * 100) // Create some seasonality
            ]);
        }

        $monthsToForecast = 4;
        $result = $this->salesForecastService->generateForecast($monthsToForecast, 'holt-winters');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('historical', $result);
        $this->assertArrayHasKey('forecast', $result);
        $this->assertCount(14, $result['historical']);
        $this->assertCount($monthsToForecast, $result['forecast']);
        $this->assertCount(14 + $monthsToForecast, $result['labels']);
    }
}
