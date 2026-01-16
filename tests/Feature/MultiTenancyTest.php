<?php

namespace Tests\Feature;

use App\Models\Categories; // Corrected model name
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// Removed Spatie actions as we are using Tenant::makeCurrent() and Tenant::forgetCurrent()
// use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
// use Spatie\Multitenancy\Actions\MakeCurrentTenantAction;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure no tenant is current before each test (important for clean state)
        Tenant::forgetCurrent();

        // Seed global permissions and roles once for the entire test run
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function tenants_cannot_see_each_others_data(): void
    {
        // 1. Create Tenant A and its user
        $tenantA = Tenant::create(['name' => 'Tenant A', 'domain' => 'tenant-a.localhost']);
        $tenantA->makeCurrent();
        $this->artisan('db:seed', ['--class' => 'UserSeeder']); // Seed user for Tenant A
        $userA = User::where('tenant_id', $tenantA->id)->first(); // Get the user created for Tenant A
        $this->assertNotNull($userA, 'User A should exist for Tenant A');
        $this->actingAs($userA);

        // Create data for Tenant A
        Categories::create(['name' => 'Category A for Tenant A', 'description' => 'Description A', 'tenant_id' => $tenantA->id]); // Explicitly set tenant_id
        $this->assertCount(1, Categories::all(), 'Tenant A should see its own category.');
        $this->assertEquals('Category A for Tenant A', Categories::first()->name);

        // 2. Create Tenant B and its user
        Tenant::forgetCurrent(); // Forget current tenant before creating new one
        $tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.localhost']);
        $tenantB->makeCurrent();
        $this->artisan('db:seed', ['--class' => 'UserSeeder']); // Seed user for Tenant B
        $userB = User::where('tenant_id', $tenantB->id)->first(); // Get the user created for Tenant B
        $this->assertNotNull($userB, 'User B should exist for Tenant B');
        $this->actingAs($userB);

        // Verify Tenant B sees no data from Tenant A
        $this->assertCount(0, Categories::all(), 'Tenant B should not see Tenant A\'s categories.');

        // Create data for Tenant B
        Categories::create(['name' => 'Category B for Tenant B', 'description' => 'Description B', 'tenant_id' => $tenantB->id]); // Explicitly set tenant_id
        $this->assertCount(1, Categories::all(), 'Tenant B should see its own category.');
        $this->assertEquals('Category B for Tenant B', Categories::first()->name);

        // 3. Switch back to Tenant A and verify data isolation
        Tenant::forgetCurrent();
        $tenantA->makeCurrent();
        $this->actingAs($userA); // Act as user A again
        $this->assertCount(1, Categories::all(), 'Tenant A should still see its own category after switching back.');
        $this->assertEquals('Category A for Tenant A', Categories::first()->name);
        $this->assertNotEquals('Category B for Tenant B', Categories::first()->name, 'Tenant A should not see Tenant B\'s categories after switching back.');
    }
}