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
            'totalSales' => 1000,
            'totalPurchases' => 500,
            'topSellingProducts' => [],
            'recentActivities' => [],
            'salesChartData' => [],
            'purchaseChartData' => [],
        ]);

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHasAll([
            'totalSales',
            'totalPurchases',
            'topSellingProducts',
            'recentActivities',
            'salesChartData',
            'purchaseChartData',
        ]);

        Mockery::close();
    }

    public function test_it_returns_chart_data_for_ajax_requests()
    {
        // Mock the DashboardService
        $mockDashboardService = Mockery::mock(DashboardService::class);
        $mockDashboardService->shouldReceive('calculateDateRange')->andReturn(['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()]);
        $mockDashboardService->shouldReceive('getChartData')
            ->with('30days', 'sales')
            ->andReturn(['labels' => ['Jan', 'Feb'], 'data' => [100, 200]]);

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->adminUser)
            ->ajaxGet(route('admin.dashboard', ['period' => '30days', 'type' => 'sales']));

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
                1
            )
            ->andReturn([
                'totalSales' => 500,
                'totalPurchases' => 200,
                'topSellingProducts' => [],
                'recentActivities' => [],
                'salesChartData' => [],
                'purchaseChartData' => [],
            ]);

        $this->app->instance(DashboardService::class, $mockDashboardService);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['date_range' => 'last_month', 'category_id' => 1]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('totalSales', 500);

        Mockery::close();
    }

    public function test_it_redirects_unauthenticated_users()
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }
}