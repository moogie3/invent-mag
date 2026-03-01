<?php

namespace Tests\Unit\Services;

use App\Exceptions\PlanLimitExceededException;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PlanService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PlanServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected PlanService $planService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->planService = new PlanService();
        
        // Ensure plans exist for our tests
        (new PlanSeeder)->run();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(PlanService::class, $this->planService);
    }

    #[Test]
    public function it_returns_null_plan_when_no_tenant()
    {
        // Clear the current tenant context temporarily
        Tenant::current()->forgetCurrent();
        
        $this->assertNull($this->planService->getCurrentPlan());
        
        // Restore context
        $this->tenant->makeCurrent();
    }

    #[Test]
    public function it_returns_available_plans()
    {
        $plans = $this->planService->getAvailablePlans();
        
        $this->assertCount(3, $plans);
        $this->assertEquals('Starter', $plans[0]->name);
        $this->assertEquals('Professional', $plans[1]->name);
        $this->assertEquals('Enterprise', $plans[2]->name);
    }

    #[Test]
    public function it_returns_current_plan_for_tenant()
    {
        $starterPlan = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starterPlan);

        $currentPlan = $this->planService->getCurrentPlan();

        $this->assertNotNull($currentPlan);
        $this->assertEquals('starter', $currentPlan->slug);
    }

    #[Test]
    public function it_checks_feature_access_correctly()
    {
        $starterPlan = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starterPlan);

        // Starter plan should have 'products' but NOT 'pos'
        $this->assertTrue($this->planService->tenantHasFeature('products'));
        $this->assertFalse($this->planService->tenantHasFeature('pos'));
        
        $professionalPlan = Plan::findBySlug('professional');
        $this->tenant->assignPlan($professionalPlan);
        $this->tenant->refresh(); // Clear relation cache

        // Professional plan should have both 'products' and 'pos', but NOT 'api_access'
        $this->assertTrue($this->planService->tenantHasFeature('products'));
        $this->assertTrue($this->planService->tenantHasFeature('pos'));
        $this->assertFalse($this->planService->tenantHasFeature('api_access'));
    }

    #[Test]
    public function it_allows_user_creation_within_limit()
    {
        $starterPlan = Plan::findBySlug('starter'); // Limit 3 users
        $this->tenant->assignPlan($starterPlan);

        // We already created 1 user in setupTenant()
        
        // Second user should be allowed
        $this->assertTrue($this->planService->canAddUser());
        User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Third user should be allowed
        $this->assertTrue($this->planService->canAddUser());
        User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function it_blocks_user_creation_at_limit()
    {
        $starterPlan = Plan::findBySlug('starter'); // Limit 3 users
        $this->tenant->assignPlan($starterPlan);

        // 1 user exists from setup
        User::factory()->create(['tenant_id' => $this->tenant->id]); // User 2
        User::factory()->create(['tenant_id' => $this->tenant->id]); // User 3

        // Fourth user should NOT be allowed
        $this->assertFalse($this->planService->canAddUser());
    }

    #[Test]
    public function it_allows_unlimited_users_for_enterprise()
    {
        $enterprisePlan = Plan::findBySlug('enterprise'); // Limit -1 (unlimited)
        $this->tenant->assignPlan($enterprisePlan);

        // Create a bunch of users
        User::factory()->count(10)->create(['tenant_id' => $this->tenant->id]);

        // Should still be allowed to add more
        $this->assertTrue($this->planService->canAddUser());
    }

    #[Test]
    public function it_can_upgrade_plan()
    {
        $starterPlan = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starterPlan);
        
        $professionalPlan = Plan::findBySlug('professional');
        
        $this->planService->upgradePlan($this->tenant, $professionalPlan->slug);

        $this->tenant->refresh();
        $this->assertEquals($professionalPlan->id, $this->tenant->plan_id);
    }

    #[Test]
    public function it_prevents_same_plan_change()
    {
        $starterPlan = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starterPlan);
        $this->tenant->refresh();
        
        $response = $this->planService->upgradePlan($this->tenant, $starterPlan->slug);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('You are already on this plan.', $response['message']);
    }

    #[Test]
    public function it_suggests_correct_upgrade_for_feature()
    {
        $suggestion = $this->planService->getSuggestedUpgrade('pos');
        
        // POS is in Professional
        $this->assertNotNull($suggestion);
        $this->assertEquals('professional', $suggestion->slug);
        
        $suggestionEnterprise = $this->planService->getSuggestedUpgrade('api_access');
        
        // API Access is in Enterprise
        $this->assertNotNull($suggestionEnterprise);
        $this->assertEquals('enterprise', $suggestionEnterprise->slug);
    }

    #[Test]
    public function it_returns_usage_stats()
    {
        $starterPlan = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starterPlan);
        
        Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $stats = $this->planService->getUsageStats();

        $this->assertArrayHasKey('users', $stats);
        $this->assertArrayHasKey('warehouses', $stats);
        
        $this->assertEquals(1, $stats['users']['current']);
        $this->assertEquals(3, $stats['users']['limit']);
        $this->assertEquals(false, $stats['users']['unlimited']);
        
        $this->assertEquals(1, $stats['warehouses']['current']);
        $this->assertEquals(1, $stats['warehouses']['limit']);
        $this->assertEquals(false, $stats['warehouses']['unlimited']);
    }

    #[Test]
    public function it_handles_legacy_tenant_without_plan()
    {
        // Ensure legacy mode is enabled
        config(['plans.legacy_full_access' => true]);
        
        // Tenant has no plan assigned
        $this->assertNull($this->tenant->plan_id);
        
        // They should have full access
        $this->assertTrue($this->planService->tenantHasFeature('api_access'));
        $this->assertTrue($this->planService->canAddUser());
        $this->assertTrue($this->planService->canAddWarehouse());
    }
}
