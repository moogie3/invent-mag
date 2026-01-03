<?php

namespace Tests\Feature\Api\V1;

use App\Models\Categories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        
        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_categories_api()
    {
        Auth::guard('web')->logout();
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/categories')
            ->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_categories()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/categories')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_categories()
    {
        Categories::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description'],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_create_category()
    {
        $payload = [
            'name' => 'API Category',
            'description' => 'Created via API',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('categories', [
            'tenant_id' => $this->tenant->id,
            'name' => 'API Category',
        ]);
    }

    #[Test]
    public function category_api_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}