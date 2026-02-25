<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->getJson('/api/v1/roles');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'guard_name',
                    ]
                ]
            ]);
    }

    public function test_store_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->postJson('/api/v1/roles', ['name' => 'new-role']);

        $response->assertUnauthorized();
    }

    public function test_store_creates_a_new_role()
    {
        $roleName = 'new-role';

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/roles', [
            'name' => $roleName,
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => $roleName
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => $roleName,
        ]);
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        $role = Role::create(['name' => 'test-role']);

        $response = $this->getJson("/api/v1/roles/{$role->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $role = Role::create(['name' => 'test-role']);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/roles/{$role->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $role->id,
                    'name' => 'test-role',
                ]
            ]);
    }

    public function test_update_returns_unauthorized_if_user_is_not_authenticated()
    {
        $role = Role::create(['name' => 'test-role']);

        $response = $this->putJson("/api/v1/roles/{$role->id}", ['name' => 'updated-role']);

        $response->assertUnauthorized();
    }

    public function test_update_modifies_an_existing_role()
    {
        $role = Role::create(['name' => 'test-role']);
        $newName = 'updated-role';

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/roles/{$role->id}", [
            'name' => $newName,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $newName
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => $newName,
        ]);
    }

    public function test_destroy_returns_unauthorized_if_user_is_not_authenticated()
    {
        $role = Role::create(['name' => 'test-role']);

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertUnauthorized();
    }

    public function test_destroy_deletes_a_role()
    {
        $role = Role::create(['name' => 'test-role']);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }
}