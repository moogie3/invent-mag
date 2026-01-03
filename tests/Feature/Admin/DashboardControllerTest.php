<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use Database\Seeders\RoleSeeder;

class DashboardControllerTest extends TestCase
{
    use WithFaker, CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class); // Seed roles and permissions
        $this->user->assignRole('superuser');
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
                'topCustomers' => [],
            ],
            'supplierAnalytics' => [
                'totalSuppliers' => 0,
                'activeSuppliers' => 0,
                'supplierPaymentPerformance' => 0,
                'avgPurchaseValue' => 0,
                'totalOutstanding' => 0,
                'topSuppliers' => [],
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
            'inCountUnpaid' => 0,
            'outCountUnpaid' => 0,
            'totalRevenue' => 0,
            'avgDueDays' => 0,
            'collectionRate' => 0,
            'keyMetrics' => [],
            'financialItems' => [],
            'invoiceStatusData' => [
                'totalInvoices' => 0,
                'collectionRate' => 0,
                'collectionRateDisplay' => 0,
                'outCount' => 0,
                'inCount' => 0,
                'outCountUnpaid' => 0,
                'inCountUnpaid' => 0,
                'outPercentage' => 0,
                'inPercentage' => 0,
                'avgDueDays' => 0,
                'arAging' => [
                    'current' => 0,
                    '1-30' => 0,
                    '31-60' => 0,
                    '61-90' => 0,
                    '90+' => 0,
                    'total_overdue' => 0,
                ],
                'apAging' => [
                    'current' => 0,
                    '1-30' => 0,
                    '31-60' => 0,
                    '61-90' => 0,
                    '90+' => 0,
                    'total_overdue' => 0,
                ],
            ],
            'customerInsights' => [
                'avgDueDays' => 0,
                'collectionRate' => 0,
                'paymentTerms' => [],
                'totalCustomers' => 0,
                'activeCustomers' => 0,
                'bgColor' => 'bg-success',
                'percentage' => 0,
            ],
            'arAging' => [
                'current' => 0,
                '1-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0,
                'total_overdue' => 0,
            ],
            'apAging' => [
                'current' => 0,
                '1-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0,
                'total_overdue' => 0,
            ],
            'salesForecast' => [
                'labels' => [],
                'historical' => [],
                'forecast' => [],
            ],
        ]);

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');

        Mockery::close();
    }

    public function test_it_returns_chart_data_for_ajax_requests()
    {
        // Mock the DashboardService
        $mockDashboardService = Mockery::mock(DashboardService::class);
        $mockDashboardService->shouldReceive('calculateDateRange')->andReturn(['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()]);
        $mockDashboardService->shouldReceive('getChartData')
            ->with('30days', 'sales')
            ->andReturn(['labels' => ['Jan', 'Feb'], 'data' => [100, 200], 'formatted' => ['100', '200']]);

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->user)
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
                'topCustomers' => [],
            ],
            'supplierAnalytics' => [
                'totalSuppliers' => 0,
                'activeSuppliers' => 0,
                'supplierPaymentPerformance' => 0,
                'avgPurchaseValue' => 0,
                'totalOutstanding' => 0,
                'topSuppliers' => [],
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
            'inCountUnpaid' => 0,
            'outCountUnpaid' => 0,
            'totalRevenue' => 0,
            'avgDueDays' => 0,
            'collectionRate' => 0,
            'keyMetrics' => [],
            'financialItems' => [],
            'invoiceStatusData' => [
                'totalInvoices' => 0,
                'collectionRate' => 0,
                'collectionRateDisplay' => 0,
                'outCount' => 0,
                'inCount' => 0,
                'outCountUnpaid' => 0,
                'inCountUnpaid' => 0,
                'outPercentage' => 0,
                'inPercentage' => 0,
                'avgDueDays' => 0,
                'arAging' => [
                    'current' => 0,
                    '1-30' => 0,
                    '31-60' => 0,
                    '61-90' => 0,
                    '90+' => 0,
                    'total_overdue' => 0,
                ],
                'apAging' => [
                    'current' => 0,
                    '1-30' => 0,
                    '31-60' => 0,
                    '61-90' => 0,
                    '90+' => 0,
                    'total_overdue' => 0,
                ],
            ],
            'customerInsights' => [
                'avgDueDays' => 0,
                'collectionRate' => 0,
                'paymentTerms' => [],
                'totalCustomers' => 0,
                'activeCustomers' => 0,
                'bgColor' => 'bg-success',
                'percentage' => 0,
            ],
            'arAging' => [
                'current' => 0,
                '1-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0,
                'total_overdue' => 0,
            ],
            'apAging' => [
                'current' => 0,
                '1-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0,
                'total_overdue' => 0,
            ],
            'salesForecast' => [
                'labels' => [],
                'historical' => [],
                'forecast' => [],
            ],
        ]);

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->user)
            ->get(route('admin.dashboard', ['date_range' => 'last_month', 'category_id' => 1]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');

        Mockery::close();
    } // Closing brace for the test method

    public function test_it_redirects_unauthenticated_users()
    {
        Auth::logout();
        Tenant::forgetCurrent();
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }
}