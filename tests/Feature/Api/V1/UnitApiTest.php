<?php

namespace Tests\Feature\Api\V1;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnitApiTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_units_index()
    {
        $response = $this->getJson('/api/v1/units');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_units()
    {
        Unit::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/units');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'short_name',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_unit_show()
    {
        $unit = Unit::factory()->create();
        $response = $this->getJson("/api/v1/units/{$unit->id}");
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_unit()
    {
        $unit = Unit::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/units/{$unit->id}");
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'short_name' => $unit->symbol,
                ]
            ]);
    }
}
