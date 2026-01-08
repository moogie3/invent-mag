<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class TaxApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
    }

    public function test_unauthenticated_user_cannot_access_taxes_index()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/taxes');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_tax_data()
    {
        Tax::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/taxes');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'rate',
                    'is_active',
                ]
            ]);
    }

    public function test_unauthenticated_user_cannot_get_active_tax_rate()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/tax/active-rate');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_active_tax_rate()
    {
        // Deactivate any existing active taxes
        Tax::where('is_active', true)->where('tenant_id', $this->tenant->id)->update(['is_active' => false]);
        // Create a specific active tax to test against
        $activeTax = Tax::factory()->create(['is_active' => true, 'tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/tax/active-rate');

        $response->assertOk()
            ->assertJsonStructure(['tax_rate'])
            ->assertJson(['tax_rate' => $activeTax->rate]);
    }
}
