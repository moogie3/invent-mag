<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create the superuser role if it doesn't exist
        $superUserRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superuser']);
        // Assign the superuser role to the admin user
        $this->adminUser->assignRole($superUserRole);
    }

    public function test_it_displays_the_dashboard_page_for_authenticated_users()
    {
        // Mock the DashboardService
        $mockDashboardService = Mockery::mock(DashboardService::class);
        $mockDashboardService->shouldReceive('calculateDateRange')->andReturn(['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()]);
        $mockDashboardService->shouldReceive('getDashboardData')->andReturn([
            'topCategories' => [],
            'monthlyData' => [],
            'chartLabels' => [],
            'chartData' => [],
            'purchaseChartLabels' => [],
            'purchaseChartData' => [],
            'customerAnalytics' => [
                'totalCustomers' => 0,
                'activeCustomers' => 0,
                'retentionRate' => 0,
                'avgOrderValue' => 0,
                'customerLifetimeValue' => 0,
                'topCustomers' => [(object)['id' => 1, 'name' => 'Dummy Customer', 'total_sales' => 0]],
            ],
            'supplierAnalytics' => [
                'totalSuppliers' => 0,
                'activeSuppliers' => 0,
                'supplierPaymentPerformance' => 0,
                'avgPurchaseValue' => 0,
                'totalOutstanding' => 0,
                'topSuppliers' => [(object)['id' => 1, 'name' => 'Dummy Supplier', 'location' => 'Dummy Location', 'total_purchases' => 0]],
            ],
            'recentTransactions' => collect([]),
            'topSellingProducts' => collect([]),
            'recentSales' => [],
            'recentPurchases' => [],
            'lowStockCount' => 0,
            'lowStockProducts' => collect([]),
            'expiringSoonItems' => collect([]),
            'totalliability' => 0,
            'countliability' => 0,
            'paidDebtMonthly' => 0,
            'countRevenue' => 0,
            'countSales' => 0,
            'liabilitypaymentMonthly' => 0,
            'inCount' => 0,
            'outCount' => 0,
            'inCountUnpaid' => 0,
            'outCountUnpaid' => 0,
            'totalRevenue' => 0,
            'avgDueDays' => 0,
            'collectionRate' => 0,
            'keyMetrics' => [
                [
                    'title' => 'Dummy Metric 1',
                    'icon' => 'ti-test',
                    'value' => 100,
                    'total' => 200,
                    'format' => 'currency',
                    'bar_color' => 'bg-primary',
                    'trend_type' => 'inverse',
                    'route' => null,
                    'percentage' => 50,
                    'trend' => 'positive',
                    'trend_label' => '50%',
                    'trend_icon' => 'ti ti-trending-up',
                    'badge_class' => 'bg-success-lt',
                ],
                [
                    'title' => 'Dummy Metric 2',
                    'icon' => 'ti-test',
                    'value' => 50,
                    'total' => 100,
                    'format' => 'numeric',
                    'bar_color' => 'bg-green',
                    'trend_type' => 'normal',
                    'route' => null,
                    'percentage' => 50,
                    'trend' => 'negative',
                    'trend_label' => '50%',
                    'trend_icon' => 'ti ti-trending-down',
                    'badge_class' => 'bg-danger-lt',
                ],
            ],
            'financialItems' => [],
            'invoiceStatusData' => [
                'totalInvoices' => 0,
                'collectionRateDisplay' => 0,
                'outCount' => 0,
                'outPercentage' => 0,
                'outCountUnpaid' => 0,
                'inCount' => 0,
                'inPercentage' => 0,
                'inCountUnpaid' => 0,
                'avgDueDays' => 0,
                'collectionRate' => 0,
            ],
            'customerInsights' => [
                'collectionRate' => 0,
                'paymentTerms' => [],
                'totalCustomers' => 0,
                'activeCustomers' => 0,
            ],
        ]); // This closes the andReturn array

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');

        Mockery::close();
    } // Closing brace for the test method

    public function test_it_returns_chart_data_for_ajax_requests()
    {
        // Mock the DashboardService
        $mockDashboardService = Mockery::mock(DashboardService::class);
        $mockDashboardService->shouldReceive('calculateDateRange')->andReturn(['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()]);
        $mockDashboardService->shouldReceive('getChartData')
            ->with('30days', 'sales')
            ->andReturn(['labels' => ['Jan', 'Feb'], 'data' => [100, 200], 'formatted' => ['100', '200']]);

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => '30days', 'type' => 'sales']), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson([
            'labels' => ['Jan', 'Feb'],
            'data' => [100, 200],
        ]);

        Mockery::close();
    }

    public function test_it_handles_date_range_and_category_id_parameters()
    {
        // Mock the DashboardService
        $mockDashboardService = Mockery::mock(DashboardService::class);
        $mockDashboardService->shouldReceive('calculateDateRange')
            ->with('last_month', null, null)
            ->andReturn(['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()]);
        $mockDashboardService->shouldReceive('getDashboardData')
            ->with(
                ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()],
                'all',
                '1'
            )
            ->andReturn([
                'topCategories' => [],
                'monthlyData' => [],
                'chartLabels' => [],
                'chartData' => [],
                'purchaseChartLabels' => [],
                'purchaseChartData' => [],
                'customerAnalytics' => [
                    'totalCustomers' => 0,
                    'activeCustomers' => 0,
                    'retentionRate' => 0,
                    'avgOrderValue' => 0,
                    'customerLifetimeValue' => 0,
                    'topCustomers' => [(object)['id' => 1, 'name' => 'Dummy Customer', 'total_sales' => 0]],
                ],
                'supplierAnalytics' => [
                    'totalSuppliers' => 0,
                    'activeSuppliers' => 0,
                    'supplierPaymentPerformance' => 0,
                    'avgPurchaseValue' => 0,
                    'totalOutstanding' => 0,
                    'topSuppliers' => [(object)['id' => 1, 'name' => 'Dummy Supplier', 'location' => 'Dummy Location', 'total_purchases' => 0]],
                ],
                'recentTransactions' => collect([]),
                'topSellingProducts' => collect([]),
                'recentSales' => [],
                'recentPurchases' => [],
                'lowStockCount' => 0,
                'lowStockProducts' => collect([]),
                'expiringSoonItems' => collect([]),
                'totalliability' => 0,
                'countliability' => 0,
                'paidDebtMonthly' => 0,
                'countRevenue' => 0,
                'countSales' => 0,
                'liabilitypaymentMonthly' => 0,
                'inCount' => 0,
                'outCount' => 0,
                'inCountUnpaid' => 0,
'outCountUnpaid' => 0,
                'totalRevenue' => 0,
                'avgDueDays' => 0,
                'collectionRate' => 0,
                'keyMetrics' => [
                    [
                        'title' => 'Dummy Metric 1',
                        'icon' => 'ti-test',
                        'value' => 100,
                        'total' => 200,
                        'format' => 'currency',
                        'bar_color' => 'bg-primary',
                        'trend_type' => 'inverse',
                        'route' => null,
                        'percentage' => 50,
                        'trend' => 'positive',
                        'trend_label' => '50%',
                        'trend_icon' => 'ti ti-trending-up',
                        'badge_class' => 'bg-success-lt',
                    ],
                    [
                        'title' => 'Dummy Metric 2',
                        'icon' => 'ti-test',
                        'value' => 50,
                        'total' => 100,
                        'format' => 'numeric',
                        'bar_color' => 'bg-green',
                        'trend_type' => 'normal',
                        'route' => null,
                        'percentage' => 50,
                        'trend' => 'negative',
                        'trend_label' => '50%',
                        'trend_icon' => 'ti ti-trending-down',
                        'badge_class' => 'bg-danger-lt',
                    ],
                ],
                'financialItems' => [],
                'invoiceStatusData' => [
                    'totalInvoices' => 0,
                    'collectionRateDisplay' => 0,
                    'outCount' => 0,
                    'outPercentage' => 0,
                    'outCountUnpaid' => 0,
                    'inCount' => 0,
                    'inPercentage' => 0,
                    'inCountUnpaid' => 0,
                    'avgDueDays' => 0,
                    'collectionRate' => 0,
                ],
                'customerInsights' => [
                    'collectionRate' => 0,
                    'paymentTerms' => [],
                    'totalCustomers' => 0,
                    'activeCustomers' => 0,
                ],
            ]); // This closes the andReturn array

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['date_range' => 'last_month', 'category_id' => 1]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');

        Mockery::close();
    } // Closing brace for the test method

    public function test_it_redirects_unauthenticated_users()
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }
}