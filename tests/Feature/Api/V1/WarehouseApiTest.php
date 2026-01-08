<?php

namespace Tests\Feature\Api\V1;

use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class WarehouseApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
    }

    public function test_unauthenticated_user_cannot_access_warehouses_index()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/warehouses');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_warehouses()
    {
        Warehouse::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/warehouses');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'is_main',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_warehouse_show()
    {
        Auth::guard('web')->logout();
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $response = $this->getJson("/api/v1/warehouses/{$warehouse->id}");
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_warehouse()
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/warehouses/{$warehouse->id}");
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'is_main' => $warehouse->is_main,
                ]
            ]);
    }
}
