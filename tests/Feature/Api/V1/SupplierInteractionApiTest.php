<?php

namespace Tests\Feature\Api\V1;

use App\Models\Supplier;
use App\Models\SupplierInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class SupplierInteractionApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_unauthenticated_user_cannot_access_supplier_interactions_index()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/supplier-interactions');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_supplier_interactions()
    {
        SupplierInteraction::factory()->count(3)->create([
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/supplier-interactions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'supplier_id',
                        'user_id',
                        'type',
                        'interaction_date',
                        'notes',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_supplier_interaction_show()
    {
        Auth::guard('web')->logout();
        $interaction = SupplierInteraction::factory()->create([
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson("/api/v1/supplier-interactions/{$interaction->id}");

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_supplier_interaction()
    {
        $interaction = SupplierInteraction::factory()->create([
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/supplier-interactions/{$interaction->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $interaction->id,
                    'supplier_id' => $this->supplier->id,
                    'user_id' => $this->user->id,
                ]
            ]);
    }
}
