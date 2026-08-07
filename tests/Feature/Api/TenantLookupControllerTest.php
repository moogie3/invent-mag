<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLookupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_lookup_existing_tenant()
    {
        $tenant = Tenant::create([
            'name' => 'Existing Shop',
            'domain' => 'existing-shop.localhost',
        ]);

        $response = $this->postJson('/api/lookup-tenant', [
            'shopname' => 'Existing Shop',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'tenant_domain' => 'existing-shop.localhost',
        ]);
    }

    public function test_lookup_returns_404_for_non_existent_tenant()
    {
        $response = $this->postJson('/api/lookup-tenant', [
            'shopname' => 'Non Existent Shop',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Shop not found.',
        ]);
    }

    public function test_lookup_requires_shopname()
    {
        $response = $this->postJson('/api/lookup-tenant', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shopname']);
    }
}
