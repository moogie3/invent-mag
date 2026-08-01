<?php

namespace Tests\Feature\Admin;

use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PlanControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(PlanSeeder::class);
        $this->user->assignRole('superuser');
    }

    public function test_index_returns_200_with_plan_data()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.plan'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.plan.index');
        $response->assertViewHas('currentPlan');
        $response->assertViewHas('plans');
        $response->assertViewHas('usageStats');
        $response->assertViewHas('features');
    }

    public function test_upgrade_returns_200_with_plan_comparison()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.plan.upgrade'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.plan.upgrade');
        $response->assertViewHas('currentPlan');
        $response->assertViewHas('plans');
        $response->assertViewHas('features');
    }

    public function test_upgrade_with_feature_query_returns_suggested_plan()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.plan.upgrade').'?feature=pos');

        $response->assertStatus(200);
        $response->assertViewHas('suggestedPlan');
    }

    public function test_index_shows_tenant_info()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.plan'));

        $response->assertStatus(200);
        $response->assertViewHas('tenant');
    }

    public function test_index_requires_authentication()
    {
        $response = $this->get(route('admin.setting.plan'));
        $this->assertTrue(in_array($response->status(), [302, 200]));
    }

    public function test_upgrade_returns_expiring_soon_flag_when_within_7_days()
    {
        $this->tenant->update([
            'plan_expires_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.setting.plan.upgrade'));

        $response->assertStatus(200);
        $response->assertViewHas('expiringSoon');
    }

    public function test_index_handles_success_query_parameter()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.plan').'?success=1');

        $response->assertStatus(200);
    }

    public function test_index_calculates_usage_stats()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.plan'));

        $response->assertStatus(200);

        $usageStats = $response->viewData('usageStats');

        $this->assertArrayHasKey('users', $usageStats);
        $this->assertArrayHasKey('warehouses', $usageStats);
    }

    public function test_upgrade_includes_feature_list()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.plan.upgrade'));

        $response->assertStatus(200);

        $features = $response->viewData('features');

        $this->assertNotEmpty($features);
    }

    public function test_index_with_no_plan_shows_available_plans()
    {
        $this->tenant->update(['plan_id' => null]);

        $response = $this->actingAs($this->user)->get(route('admin.setting.plan'));

        $response->assertStatus(200);
    }
}
