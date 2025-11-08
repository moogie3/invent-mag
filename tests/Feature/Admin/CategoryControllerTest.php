<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Categories;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);
    }

    public function test_it_can_display_the_category_index_page()
    {
        $response = $this->get(route('admin.setting.category'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.category.index');
    }

    public function test_it_can_store_a_new_category()
    {
        $categoryData = [
            'name' => 'New Category',
            'description' => 'A new category.',
        ];

        $response = $this->post(route('admin.setting.category.store'), $categoryData);

        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('success', 'Category created');

        $this->assertDatabaseHas('categories', $categoryData);
    }

    public function test_it_can_update_a_category()
    {
        $category = Categories::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated category description.',
        ];

        $response = $this->put(route('admin.setting.category.update', $category->id), $updateData);

        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('success', 'Category updated');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name',
        ]);
    }

    public function test_it_can_delete_a_category()
    {
        $category = Categories::factory()->create();

        $response = $this->delete(route('admin.setting.category.destroy', $category->id));

        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('success', 'Category deleted');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
