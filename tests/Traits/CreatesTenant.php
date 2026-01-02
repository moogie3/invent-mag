<?php

namespace Tests\Traits;

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait CreatesTenant
{
    protected Tenant $tenant;
    protected User $user;

    public function setupTenant()
    {
        // Create a tenant
        $this->tenant = Tenant::create(['name' => 'Test Tenant', 'domain' => 'test.localhost']);

        // Set the tenant as the current tenant
        $this->tenant->makeCurrent();

        // Create a user for this tenant
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Assign a role to the user
        $role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
        $this->user->assignRole($role);

        // Act as this user for the test
        $this->actingAs($this->user);
    }
}
