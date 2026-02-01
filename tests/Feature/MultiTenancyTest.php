<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::forgetCurrent();
    }

    /** @test */
    public function tenants_cannot_see_each_others_data(): void
    {
        // 1. Setup Tenant A
        $tenantA = Tenant::create(['name' => 'Tenant A', 'domain' => 'tenant-a.localhost']);
        $tenantA->makeCurrent();
        $userA = User::factory()->create(['email' => 'user@tenant-a.com', 'tenant_id' => $tenantA->id]);
        
        // 2. Setup Tenant B
        Tenant::forgetCurrent();
        $tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.localhost']);
        $tenantB->makeCurrent();
        $userB = User::factory()->create(['email' => 'user@tenant-b.com', 'tenant_id' => $tenantB->id]);

        // 3. Verify Isolation
        $this->actingAs($userB);
        // Ensure standard queries obey tenant scope (example with Users table itself)
        $this->assertNull(User::find($userA->id), 'Tenant B should not find User A via standard query.');
        $this->assertNotNull(User::find($userB->id), 'Tenant B should find User B.');
    }

    /** @test */
    public function cross_tenant_login_is_blocked()
    {
        // 1. Setup Tenant A with a user
        $tenantA = Tenant::create(['name' => 'Tenant A', 'domain' => 'tenant-a.localhost']);
        $userA = User::factory()->create([
            'email' => 'admin@tenant-a.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantA->id
        ]);

        // 2. Setup Tenant B (Empty)
        $tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.localhost']);

        // 3. Simulate being on Tenant B
        $tenantB->makeCurrent();

        // 4. Attempt to login using Tenant A's credentials
        // We use the route name to ensure we hit the correct endpoint, 
        // effectively simulating being on the current tenant's login page
        $response = $this->post(route('admin.login'), [
            'email' => 'admin@tenant-a.com',
            'password' => 'password',
        ]);

        // 5. Expectation: Login Fails (Validation Error)
        // because User A does not exist in Tenant B's scope.
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function correct_tenant_login_succeeds()
    {
        // 1. Setup Tenant A with a user
        $tenantA = Tenant::create(['name' => 'Tenant A', 'domain' => 'tenant-a.localhost']);
        $userA = User::factory()->create([
            'email' => 'admin@tenant-a.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantA->id
        ]);

        // 2. Simulate being on Tenant A
        $tenantA->makeCurrent();

        // 3. Attempt to login
        $response = $this->post(route('admin.login'), [
            'email' => 'admin@tenant-a.com',
            'password' => 'password',
        ]);

        // 4. Expectation: Login Succeeds
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertEquals($userA->id, auth()->id());
    }
}
