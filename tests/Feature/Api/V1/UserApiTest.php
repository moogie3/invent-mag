<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class UserApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);

        $this->regularUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_unauthenticated_user_cannot_access_users_index()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/users');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_users()
    {
        User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                    ]
                ]
            ]);
    }

    public function test_unauthenticated_user_cannot_access_user_show()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson("/api/v1/users/{$this->regularUser->id}");
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_user()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/users/{$this->regularUser->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->regularUser->id,
                    'name' => $this->regularUser->name,
                    'email' => $this->regularUser->email,
                ]
            ]);
    }

    public function test_unauthenticated_user_cannot_get_roles_permissions()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/users/roles-permissions');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_roles_permissions()
    {
        // Create some roles and permissions to ensure they exist
        Role::create(['name' => 'editor']);
        Permission::create(['name' => 'edit articles']);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/users/roles-permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'rolePermissions',
                'allPermissions',
            ]);
    }
}
