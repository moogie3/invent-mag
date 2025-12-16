<?php

namespace Tests\Feature\Api\V1;

use App\Models\Categories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermissions;

    public function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'view-categories',
            'create-categories',
            'edit-categories',
            'delete-categories',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);

        // Create a user without permissions
        $this->userWithoutPermissions = User::factory()->create();
    }

    #[Test]
    public function test_unauthenticated_user_cannot_get_categories()
    {
        $response = $this->getJson('/api/v1/categories');
        $response->assertStatus(401);
    }

    #[Test]
    public function test_unauthorized_user_cannot_get_categories()
    {
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/categories');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_all_categories()
    {
        Categories::factory()->count(3)->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/categories');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                ]
            ]
        ]);
    }
    
    #[Test]
    public function test_can_get_a_category()
    {
        $category = Categories::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/categories/' . $category->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
            ]
        ]);
        $response->assertJsonFragment(['id' => $category->id]);
    }

    #[Test]
    public function test_store_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/categories', ['name' => '']);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function test_store_fails_with_duplicate_name()
    {
        Categories::factory()->create(['name' => 'Existing Category']);
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/categories', ['name' => 'Existing Category']);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function test_can_create_a_category()
    {
        $categoryData = [
            'name' => 'New API Category',
            'description' => 'A category created via API.',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/categories', $categoryData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'New API Category',
        ]);
    }

    #[Test]
    public function test_can_update_a_category()
    {
        $category = Categories::factory()->create();
        $updateData = [
            'name' => 'Updated API Category',
        ];
    
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/categories/' . $category->id, $updateData);
    
        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id, 
            'name' => 'Updated API Category',
        ]);
    }

    #[Test]
    public function test_can_delete_a_category()
    {
        $category = Categories::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/categories/' . $category->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}