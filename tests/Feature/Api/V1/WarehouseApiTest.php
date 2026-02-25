<?php

namespace Tests\Feature\Api\V1;

use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
    }

    public function test_unauthenticated_user_cannot_access_warehouses_index()
    {
        $response = $this->getJson('/api/v1/warehouses');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_warehouses()
    {
        Warehouse::factory()->count(3)->create();

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
        $warehouse = Warehouse::factory()->create();
        $response = $this->getJson("/api/v1/warehouses/{$warehouse->id}");
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_warehouse()
    {
        $warehouse = Warehouse::factory()->create();
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
