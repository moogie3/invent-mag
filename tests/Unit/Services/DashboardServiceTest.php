<?php

namespace Tests\Unit\Services;

use App\Models\Categories;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AgingReportService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class DashboardServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->dashboardService = new DashboardService;
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
        $product = Product::factory()->withStock(5)->create(['category_id' => $category->id, 'low_stock_threshold' => 10]);

        // Sales data for this month
        $sale1 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'total' => 100,
            'status' => 'Paid',
        ]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $product->id, 'quantity' => 1, 'customer_price' => 100]);

        $sale2 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'total' => 200,
            'status' => 'Unpaid',
        ]);
        SalesItem::factory()->create(['sales_id' => $sale2->id, 'product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100]);

        // Purchase data
        $purchase1 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now(),
            'total' => 50,
            'status' => 'Paid',
            'due_date' => now()->addDays(30),
        ]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $product->id, 'quantity' => 5, 'price' => 10, 'expiry_date' => null]);

        $purchase2 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now(),
            'total' => 80,
            'status' => 'Unpaid',
            'due_date' => now()->subDay(), // Overdue
        ]);
        // This POItem expires soon (within 30 days)
        POItem::factory()->create(['po_id' => $purchase2->id, 'product_id' => $product->id, 'quantity' => 8, 'price' => 10, 'expiry_date' => now()->addDays(5)]);

        // This POItem does not expire soon (outside 30 days)
        POItem::factory()->create(['po_id' => $purchase2->id, 'product_id' => $product->id, 'quantity' => 10, 'price' => 10, 'expiry_date' => now()->addDays(60)]);

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

    #[Test]
    public function it_counts_null_threshold_products_as_low_stock()
    {
        $product = Product::factory()->withStock(5)->create(['low_stock_threshold' => null]);

        $data = $this->dashboardService->getDashboardData();

        $this->assertSame(1, $data['lowStockCount']);
        $this->assertTrue(
            $data['lowStockProducts']->contains(fn ($pw) => $pw->product_id === $product->id)
        );
    }

    /**
     * An invoice due today is not yet overdue.
     *
     * The aging SQL bucketed on `DATEDIFF(NOW(), due_date) >= 0`, which puts
     * a document due today into the 1-30 day overdue bucket, and on SQLite
     * `julianday('now') - julianday(due_date)` is a positive fraction of a
     * day for the same document, so both drivers got it wrong in the same
     * direction. AgingReportService already treats it as current, which is
     * what its own test pins, so the dashboard and the health page were the
     * ones out of step.
     */
    #[Test]
    public function an_invoice_due_today_is_current_rather_than_overdue(): void
    {
        $customer = Customer::factory()->create();

        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Unpaid',
            'due_date' => now()->startOfDay(),
            'total' => 100,
        ]);

        $data = $this->dashboardService->getDashboardData();

        $this->assertEquals(
            100,
            $data['arAging']['current'],
            'A sale due today belongs in Current, not in an overdue bucket.'
        );
        $this->assertEquals(
            0,
            $data['arAging']['1-30'],
            'A sale due today must not be reported as 1-30 days overdue.'
        );
    }

    /**
     * A document genuinely past its due date still lands in the overdue
     * bucket, so tightening the boundary must not swallow the real cases.
     */
    #[Test]
    public function an_invoice_five_days_overdue_still_lands_in_the_overdue_bucket(): void
    {
        $customer = Customer::factory()->create();

        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Unpaid',
            'due_date' => now()->subDays(5)->startOfDay(),
            'total' => 100,
        ]);

        $data = $this->dashboardService->getDashboardData();

        $this->assertEquals(0, $data['arAging']['current']);
        $this->assertEquals(100, $data['arAging']['1-30']);
    }

    /**
     * Aging answers "how much is still owed", so a partially collected
     * invoice must contribute its outstanding balance, not the gross total
     * it was originally billed for.
     */
    #[Test]
    public function ar_aging_counts_the_balance_still_owed_not_the_gross_total(): void
    {
        $customer = Customer::factory()->create();

        $sale = Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Partial',
            'due_date' => now()->subDays(5)->startOfDay(),
            'total' => 1000,
        ]);

        Payment::factory()->create([
            'paymentable_id' => $sale->id,
            'paymentable_type' => Sales::class,
            'amount' => 400,
            'payment_date' => now(),
        ]);

        $data = $this->dashboardService->getDashboardData();

        $this->assertEquals(
            600,
            $data['arAging']['1-30'],
            'Aging must report the 600 still owed, not the 1000 billed.'
        );
    }

    /**
     * The dashboard, the financial health page and the aged receivables
     * report are three readers of one number. They used to be three
     * separate formulas, so the same debt could show a different amount
     * depending on which screen it was read from.
     */
    #[Test]
    public function the_dashboard_and_the_aging_report_read_the_same_figure(): void
    {
        $customer = Customer::factory()->create();

        $sale = Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Partial',
            'due_date' => now()->subDays(5)->startOfDay(),
            'total' => 1000,
        ]);

        Payment::factory()->create([
            'paymentable_id' => $sale->id,
            'paymentable_type' => Sales::class,
            'amount' => 400,
            'payment_date' => now(),
        ]);

        $dashboard = $this->dashboardService->getDashboardData();
        $report = app(AgingReportService::class)->generateAgedReceivables();

        $this->assertEquals(
            $dashboard['arAging']['1-30'],
            (float) $report['1-30']->sum('balance'),
            'Dashboard and report must not disagree about the same debt.'
        );
        $this->assertEquals(600, (float) $report['1-30']->sum('balance'));
    }

    /**
     * Buying stock is not selling it. COGS is what the ledger booked
     * against the goods that actually left the warehouse, not the month's
     * purchase total: buying 5000 of stock and selling 700 of it is a 300
     * gross profit, not a 4700 loss.
     */
    #[Test]
    public function gross_profit_costs_only_the_stock_that_was_actually_sold(): void
    {
        // 5000 of stock bought this month, plus a 1000 sale.
        Purchase::factory()->create([
            'order_date' => now(),
            'total' => 5000,
            'status' => 'Unpaid',
        ]);
        Sales::factory()->create([
            'order_date' => now(),
            'total' => 1000,
            'status' => 'Paid',
        ]);

        // The sale's journal entry debited COGS for the 700 that left.
        $this->bookCostOfGoodsSold(700);

        $data = $this->dashboardService->getDashboardData();

        $grossProfit = collect($data['financialItems'])
            ->firstWhere('label', __('messages.gross_profit'))['value'];

        $this->assertEquals(
            300,
            $grossProfit,
            'Gross profit must cost the 700 sold, not the 5000 bought.'
        );
    }

    /**
     * The analytics tables render $item->name straight from these rows, so a
     * wrong relation name silently degrades every row to the "Unknown ..."
     * fallback while the customer/supplier name sits right there in the join.
     */
    #[Test]
    public function top_customers_and_suppliers_show_their_real_names(): void
    {
        $customer = Customer::factory()->create(['name' => 'Acme Trading']);
        Sales::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'total' => 500,
        ]);

        $supplier = Supplier::factory()->create(['name' => 'Global Parts', 'location' => 'IN']);
        Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now(),
            'total' => 700,
        ]);

        $data = $this->dashboardService->getDashboardData();

        $topCustomer = $data['customerAnalytics']['topCustomers']->first();
        $this->assertEquals(
            'Acme Trading',
            $topCustomer->name,
            'Top customer table must show the joined customer name, not Unknown Customer.'
        );

        $topSupplier = $data['supplierAnalytics']['topSuppliers']->first();
        $this->assertEquals(
            'Global Parts',
            $topSupplier->name,
            'Top supplier table must show the joined supplier name, not Unknown Supplier.'
        );
        $this->assertEquals('IN', $topSupplier->location);
    }

    /**
     * Net profit must earn and spend the same period: this month's revenue
     * against this month's purchases. It used to subtract every purchase the
     * tenant had ever made from a single month of revenue, so the tile slid
     * further into the red every month the business traded.
     */
    #[Test]
    public function net_profit_subtracts_only_this_months_purchases(): void
    {
        // Bought two months ago: belongs to an earlier profit window.
        Purchase::factory()->create([
            'order_date' => now()->subMonthsNoOverflow(2)->startOfMonth()->addDay(),
            'status' => 'Paid',
            'total' => 5000,
        ]);

        // Bought this month: the only purchase this month's profit may see.
        Purchase::factory()->create([
            'order_date' => now()->startOfMonth()->addDay(),
            'status' => 'Paid',
            'total' => 100,
        ]);

        Sales::factory()->create([
            'order_date' => now(),
            'status' => 'Paid',
            'total' => 1000,
        ]);

        $data = $this->dashboardService->getDashboardData();

        $netProfit = collect($data['financialItems'])
            ->firstWhere('label', __('messages.net_profit'))['value'];

        $this->assertEqualsWithDelta(
            900,
            $netProfit,
            0.005,
            'Net profit must charge this month\'s 100 purchase against this month\'s 1000 revenue, not the 5100 bought in total.'
        );
    }

    /**
     * The "Total Account Receivable" tile must show money customers still
     * owe. It displayed this month's revenue instead, so a fully collected
     * month rendered as a huge receivable while a real unpaid invoice never
     * appeared on it.
     */
    #[Test]
    public function account_receivable_tile_shows_outstanding_balances(): void
    {
        // Unpaid invoice the customer still owes.
        Sales::factory()->create([
            'status' => 'Unpaid',
            'total' => 700,
            'order_date' => now(),
            'due_date' => now()->addWeek(),
        ]);

        // Fully paid sale: revenue, but nothing left to collect.
        Sales::factory()->create([
            'status' => 'Paid',
            'total' => 1000,
            'order_date' => now(),
        ]);

        $data = $this->dashboardService->getDashboardData();

        $receivable = collect($data['financialItems'])
            ->firstWhere('label', __('messages.total_account_receivable'))['value'];

        $this->assertEqualsWithDelta(
            700,
            $receivable,
            0.005,
            'The receivable tile must count the 700 still owed, not the 1700 of revenue booked this month.'
        );
    }

    /**
     * Book one posted journal entry that moves cost out of inventory and
     * into the tenant's configured COGS account, the same account every
     * sale and POS entry posts to.
     */
    private function bookCostOfGoodsSold(float $amount): void
    {
        $tenant = app('currentTenant');

        $entry = JournalEntry::create([
            'tenant_id' => $tenant->id,
            'date' => now()->toDateString(),
            'description' => 'cost of goods sold',
            'status' => 'posted',
        ]);

        DB::table('transactions')->insert([
            [
                'journal_entry_id' => $entry->id,
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->getAccountingSetting('cost_of_goods_sold_account_id'),
                'type' => 'debit',
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $entry->id,
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->getAccountingSetting('inventory_account_id'),
                'type' => 'credit',
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
