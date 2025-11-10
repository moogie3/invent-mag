<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Categories;
use App\Services\CategoryService;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected $categoryServiceMock;

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

        // Mock the CategoryService
        $this->categoryServiceMock = Mockery::mock(CategoryService::class);
        $this->app->instance(CategoryService::class, $this->categoryServiceMock);
    }

    public function test_it_can_display_the_category_index_page()
    {
        $categories = Categories::factory()->count(3)->create();
        $perPage = 10;
        $currentPage = 1;
        $total = $categories->count();

        $paginatedCategories = new LengthAwarePaginator(
            $categories->forPage($currentPage, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => route('admin.setting.category')]
        );

        $categoryData = [
            'categories' => $paginatedCategories,
            'entries' => $perPage,
            'totalcategory' => $total,
        ];

        $this->categoryServiceMock->shouldReceive('getCategoryIndexData')
            ->once()
            ->andReturn($categoryData);

        $response = $this->get(route('admin.setting.category'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.category.index');
        $response->assertViewHas('categories', $categoryData['categories']);
        $response->assertViewHas('entries', $categoryData['entries']);
    }

    public function test_it_can_store_a_new_category()
    {
        $categoryData = [
            'name' => 'New Category',
            'description' => 'A new category.',
        ];

        $this->categoryServiceMock->shouldReceive('createCategory')
            ->once()
            ->with(Mockery::subset($categoryData))
            ->andReturn(['success' => true, 'category' => Categories::factory()->make($categoryData)]);

        $response = $this->post(route('admin.setting.category.store'), $categoryData);

        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('success', 'Category created');

        // We no longer assert database has since the service is mocked
        // $this->assertDatabaseHas('categories', $categoryData);
    }

    public function test_store_category_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'name' => '', // Required
            'description' => '', // Required
        ];

        $this->categoryServiceMock->shouldNotReceive('createCategory');

        $response = $this->post(route('admin.setting.category.store'), $invalidData);

        $response->assertSessionHasErrors(['name', 'description']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_store_category_handles_service_level_error()
    {
        $categoryData = [
            'name' => 'New Category',
            'description' => 'A new category.',
        ];

        $this->categoryServiceMock->shouldReceive('createCategory')
            ->once()
            ->with(Mockery::subset($categoryData))
            ->andReturn(['success' => false, 'message' => 'Category creation failed.']);

        $response = $this->post(route('admin.setting.category.store'), $categoryData);

        $response->assertSessionHasErrors(['name' => 'Category creation failed.']);
        $response->assertStatus(302);
    }

    public function test_it_can_update_a_category()
    {
        $category = Categories::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated category description.',
        ];

        $this->categoryServiceMock->shouldReceive('updateCategory')
            ->once()
            ->with(Mockery::on(function ($arg) use ($category) {
                return $arg->id === $category->id;
            }), Mockery::subset($updateData))
            ->andReturn(['success' => true, 'category' => $category->fresh()]);

        $response = $this->put(route('admin.setting.category.update', $category->id), $updateData);

        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('success', 'Category updated');

        // We no longer assert database has since the service is mocked
        // $this->assertDatabaseHas('categories', [
        //     'id' => $category->id,
        //     'name' => 'Updated Category Name',
        // ]);
    }

    public function test_it_can_delete_a_category()
    {
        $category = Categories::factory()->create();

        $this->categoryServiceMock->shouldReceive('deleteCategory')
            ->once()
            ->with(Mockery::on(function ($arg) use ($category) {
                return $arg->id === $category->id;
            }))
            ->andReturn(['success' => true]);

        $response = $this->delete(route('admin.setting.category.destroy', $category->id));

        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('success', 'Category deleted');

        // We no longer assert database missing since the service is mocked
        // $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_update_category_with_invalid_data_returns_validation_errors()
    {
        $category = Categories::factory()->create();
        $invalidData = [
            'name' => '', // Required
            'description' => '', // Required
        ];

        $this->categoryServiceMock->shouldNotReceive('updateCategory');

        $response = $this->put(route('admin.setting.category.update', $category->id), $invalidData);

        $response->assertSessionHasErrors(['name', 'description']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_update_category_handles_service_level_error()
    {
        $category = Categories::factory()->create();
        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated category description.',
        ];

        $this->categoryServiceMock->shouldReceive('updateCategory')
            ->once()
            ->with(Mockery::on(function ($arg) use ($category) {
                return $arg->id === $category->id;
            }), Mockery::subset($updateData))
            ->andReturn(['success' => false, 'message' => 'Category update failed.']);

        $response = $this->put(route('admin.setting.category.update', $category->id), $updateData);

        $response->assertSessionHasErrors(['name' => 'Category update failed.']);
        $response->assertStatus(302);
    }
}
