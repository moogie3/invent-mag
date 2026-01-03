<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermissions;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->userWithoutPermissions = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function test_non_existent_endpoint_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/non-existent-endpoint');
        $response->assertStatus(404);
    }

    #[Test]
    public function test_unauthenticated_user_receives_401()
    {
        Auth::guard('web')->logout();
        // Use a real, simple endpoint for this check
        $response = $this->getJson('/api/v1/users/roles-permissions');
        $response->assertStatus(401);
    }

    #[Test]
    public function test_authenticated_user_receives_200_for_allowed_endpoint()
    {
        // This endpoint should be accessible by any authenticated user.
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/users/roles-permissions');
        $response->assertStatus(200);
    }

    #[Test]
    public function test_roles_permissions_endpoint_returns_correct_json_structure()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/users/roles-permissions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'rolePermissions',
            'allPermissions',
        ]);
    }

    #[Test]
    public function test_api_is_protected_by_auth_sanctum()
    {
        Auth::guard('web')->logout();
        // Attempt to access a protected route without authentication
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(401); // Expect Unauthenticated

        // Attempt to access the same route with authentication
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->givePermissionTo('view-products'); // Assuming this permission exists
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/products');
        $response->assertStatus(200); // Expect OK
    }
}