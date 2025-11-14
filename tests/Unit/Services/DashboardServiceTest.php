<?php

namespace Tests\Unit\Services;

use App\Models\Categories;
use App\Models\Customer;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = new DashboardService();
        User::truncate();
        Customer::truncate();
        Supplier::truncate();
        Categories::truncate();
        Product::truncate();
        Sales::truncate();
        SalesItem::truncate();
        Purchase::truncate();
        POItem::truncate();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(DashboardService::class, $this->dashboardService);
    }

    #[Test]
    public function it_can_get_dashboard_data()
    {
        // 1. Setup data
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $supplier = Supplier::factory()->create(['location' => 'IN']);
        $category = Categories::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock_quantity' => 5, 'low_stock_threshold' => 10]);

        // Sales data for this month
        $sale1 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'total' => 100,
            'status' => 'Paid'
        ]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $product->id, 'quantity' => 1, 'customer_price' => 100]);

        $sale2 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'total' => 200,
            'status' => 'Unpaid'
        ]);
        SalesItem::factory()->create(['sales_id' => $sale2->id, 'product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100]);

        // Purchase data
        $purchase1 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now(),
            'total' => 50,
            'status' => 'Paid',
            'due_date' => now()->addDays(30)
        ]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $product->id, 'quantity' => 5, 'price' => 10]);

        $purchase2 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now(),
            'total' => 80,
            'status' => 'Unpaid',
            'due_date' => now()->subDay() // Overdue
        ]);
        POItem::factory()->create(['po_id' => $purchase2->id, 'product_id' => $product->id, 'quantity' => 8, 'price' => 10, 'expiry_date' => now()->addDays(5)]);


        // 2. Call the service method
        $data = $this->dashboardService->getDashboardData();

        // 3. Assertions
        $this->assertArrayHasKey('keyMetrics', $data);
        $this->assertArrayHasKey('financialItems', $data);
        $this->assertArrayHasKey('invoiceStatusData', $data);
        $this->assertArrayHasKey('customerInsights', $data);
        $this->assertArrayHasKey('customerAnalytics', $data);
        $this->assertArrayHasKey('supplierAnalytics', $data);
        $this->assertArrayHasKey('recentTransactions', $data);
        $this->assertArrayHasKey('topSellingProducts', $data);
        $this->assertArrayHasKey('recentSales', $data);
        $this->assertArrayHasKey('recentPurchases', $data);
        $this->assertArrayHasKey('lowStockCount', $data);
        $this->assertArrayHasKey('lowStockProducts', $data);
        $this->assertArrayHasKey('expiringSoonItems', $data);
        $this->assertArrayHasKey('topCategories', $data);
        $this->assertArrayHasKey('monthlyData', $data);

        // Assert some specific values
        $this->assertEquals(130, $data['totalliability']);
        $this->assertEquals(80, $data['countliability']);
        $this->assertEquals(300, $data['totalRevenue']);
        $this->assertEquals(200, $data['countRevenue']);
        $this->assertEquals(100, $data['countSales']);
        $this->assertEquals(1, $data['lowStockCount']);
        $this->assertCount(1, $data['lowStockProducts']);
        $this->assertCount(1, $data['expiringSoonItems']);
        $this->assertCount(2, $data['recentSales']);
        $this->assertCount(2, $data['recentPurchases']);
        $this->assertCount(1, $data['topSellingProducts']);
        $this->assertEquals(1, $data['keyMetrics'][3]['value']); // Overdue invoices count
    }


    #[Test]
    public function it_calculates_date_range_for_today()
    {
        $dates = $this->dashboardService->calculateDateRange('today');
        $this->assertEquals(Carbon::now()->startOfDay(), $dates['start']);
        $this->assertEquals(Carbon::now()->endOfDay(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_yesterday()
    {
        $dates = $this->dashboardService->calculateDateRange('yesterday');
        $this->assertEquals(Carbon::now()->subDay()->startOfDay(), $dates['start']);
        $this->assertEquals(Carbon::now()->subDay()->endOfDay(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_this_week()
    {
        $dates = $this->dashboardService->calculateDateRange('this_week');
        $this->assertEquals(Carbon::now()->startOfWeek(), $dates['start']);
        $this->assertEquals(Carbon::now()->endOfWeek(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_last_week()
    {
        $dates = $this->dashboardService->calculateDateRange('last_week');
        $this->assertEquals(Carbon::now()->subWeek()->startOfWeek(), $dates['start']);
        $this->assertEquals(Carbon::now()->subWeek()->endOfWeek(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_this_month()
    {
        $dates = $this->dashboardService->calculateDateRange('this_month');
        $this->assertEquals(Carbon::now()->startOfMonth(), $dates['start']);
        $this->assertEquals(Carbon::now()->endOfMonth(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_last_month()
    {
        $dates = $this->dashboardService->calculateDateRange('last_month');
        $this->assertEquals(Carbon::now()->subMonth()->startOfMonth(), $dates['start']);
        $this->assertEquals(Carbon::now()->subMonth()->endOfMonth(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_this_quarter()
    {
        $dates = $this->dashboardService->calculateDateRange('this_quarter');
        $this->assertEquals(Carbon::now()->startOfQuarter(), $dates['start']);
        $this->assertEquals(Carbon::now()->endOfQuarter(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_this_year()
    {
        $dates = $this->dashboardService->calculateDateRange('this_year');
        $this->assertEquals(Carbon::now()->startOfYear(), $dates['start']);
        $this->assertEquals(Carbon::now()->endOfYear(), $dates['end']);
    }

    #[Test]
    public function it_calculates_date_range_for_custom_dates()
    {
        $start = '2023-01-01';
        $end = '2023-01-31';
        $dates = $this->dashboardService->calculateDateRange('custom', $start, $end);
        $this->assertEquals(Carbon::parse($start)->startOfDay(), $dates['start']);
        $this->assertEquals(Carbon::parse($end)->endOfDay(), $dates['end']);
    }

    #[Test]
    public function it_defaults_to_this_month_for_invalid_range()
    {
        $dates = $this->dashboardService->calculateDateRange('invalid_range');
        $this->assertEquals(Carbon::now()->startOfMonth(), $dates['start']);
        $this->assertEquals(Carbon::now()->endOfMonth(), $dates['end']);
    }

    #[Test]
    public function it_can_get_chart_data_for_sales()
    {
        // 1. Setup data
        Sales::factory()->create(['order_date' => now()->subDays(5), 'total' => 100]);
        Sales::factory()->create(['order_date' => now()->subDays(2), 'total' => 200]);
        Sales::factory()->create(['order_date' => now()->subDays(2), 'total' => 50]);

        // 2. Call the service method for a 7-day period
        $chartData = $this->dashboardService->getChartData('7days', 'sales');

        // 3. Assertions
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertArrayHasKey('formatted', $chartData);

        // Expected data based on setup
        $expectedLabels = [
            now()->subDays(5)->toDateString(),
            now()->subDays(2)->toDateString(),
        ];
        $expectedData = [100, 250]; // 200 + 50 on the same day
        $expectedFormattedData = ['100', '250'];

        $this->assertEquals($expectedLabels, $chartData['labels']);
        $this->assertEquals($expectedData, $chartData['data']);
        $this->assertEquals($expectedFormattedData, $chartData['formatted']);
    }

    #[Test]
    public function it_can_get_chart_data_for_purchases()
    {
        // 1. Setup data
        Purchase::factory()->create(['order_date' => now()->subDays(10), 'total' => 150]);
        Purchase::factory()->create(['order_date' => now()->subDays(1), 'total' => 300]);

        // 2. Call the service method for a 30-day period
        $chartData = $this->dashboardService->getChartData('30days', 'purchases');

        // 3. Assertions
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertArrayHasKey('formatted', $chartData);

        // Expected data based on setup
        $expectedLabels = [
            now()->subDays(10)->toDateString(),
            now()->subDays(1)->toDateString(),
        ];
        $expectedData = [150, 300];
        $expectedFormattedData = ['150', '300'];

        $this->assertEquals($expectedLabels, $chartData['labels']);
        $this->assertEquals($expectedData, $chartData['data']);
        $this->assertEquals($expectedFormattedData, $chartData['formatted']);
    }
}
