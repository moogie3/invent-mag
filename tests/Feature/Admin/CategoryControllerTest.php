<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Categories;
use App\Services\CategoryService;
use Mockery;
use Tests\Feature\BaseFeatureTestCase;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryControllerTest extends BaseFeatureTestCase
{
    protected User $user;
    protected $categoryServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        // Mock the CategoryService
        $this->categoryServiceMock = Mockery::mock(CategoryService::class);
        $this->app->instance(CategoryService::class, $this->categoryServiceMock);
    }

    public function test_it_can_display_the_category_index_page()
    {
        // Use create() instead of make() to have persisted models
        $categories = Categories::factory()->count(3)->create();
        $perPage = 10;
        $currentPage = 1;
        $total = $categories->count();

        $paginatedCategories = new LengthAwarePaginator(
            $categories,
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

        // Use a created model for the return value to ensure it has an ID and timestamps
        $createdCategory = Categories::factory()->create($categoryData);

        $this->categoryServiceMock->shouldReceive('createCategory')
            ->once()
            ->with(Mockery::subset($categoryData))
            ->andReturn(['success' => true, 'category' => $createdCategory]);

        $response = $this->post(route('admin.setting.category.store'), $categoryData);

        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('success', 'Category created');
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
    }

    public function test_it_can_store_a_new_category_via_ajax()
    {
        $categoryData = [
            'name' => 'AJAX Category',
            'description' => 'An AJAX category.',
        ];

        $this->categoryServiceMock->shouldReceive('createCategory')
            ->once()
            ->with(Mockery::subset($categoryData))
            ->andReturn(['success' => true]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ])->json('POST', route('admin.setting.category.store'), $categoryData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Category created successfully.']);
    }

    public function test_store_category_handles_service_level_error_via_ajax()
    {
        $categoryData = [
            'name' => 'AJAX Category',
            'description' => 'An AJAX category.',
        ];

        $this->categoryServiceMock->shouldReceive('createCategory')
            ->once()
            ->with(Mockery::subset($categoryData))
            ->andReturn(['success' => false, 'message' => 'AJAX creation failed.']);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ])->json('POST', route('admin.setting.category.store'), $categoryData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'AJAX creation failed.',
                'errors' => ['name' => ['AJAX creation failed.']]
            ]);
    }

    public function test_it_can_update_a_category_via_ajax()
    {
        $category = Categories::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Category',
            'description' => 'Updated AJAX description.',
        ];

        $this->categoryServiceMock->shouldReceive('updateCategory')
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ])->json('PUT', route('admin.setting.category.update', $category->id), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Category updated successfully.']);
    }

    public function test_update_category_handles_service_level_error_via_ajax()
    {
        $category = Categories::factory()->create();
        $updateData = [
            'name' => 'Updated AJAX Category',
            'description' => 'Updated AJAX description.',
        ];

        $this->categoryServiceMock->shouldReceive('updateCategory')
            ->once()
            ->andReturn(['success' => false, 'message' => 'AJAX update failed.']);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ])->json('PUT', route('admin.setting.category.update', $category->id), $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'AJAX update failed.',
                'errors' => ['name' => ['AJAX update failed.']]
            ]);
    }

    public function test_it_can_delete_a_category_via_ajax()
    {
        $category = Categories::factory()->create();

        $this->categoryServiceMock->shouldReceive('deleteCategory')
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ])->json('DELETE', route('admin.setting.category.destroy', $category->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Category deleted successfully.']);
    }

    public function test_destroy_category_handles_service_level_error()
    {
        $category = Categories::factory()->create();

        $this->categoryServiceMock->shouldReceive('deleteCategory')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Deletion failed.']);

        // Web request
        $response = $this->delete(route('admin.setting.category.destroy', $category->id));
        $response->assertRedirect(route('admin.setting.category'));
        $response->assertSessionHas('error', 'Deletion failed.');
    }

    public function test_destroy_category_handles_service_level_error_via_ajax()
    {
        $category = Categories::factory()->create();

        $this->categoryServiceMock->shouldReceive('deleteCategory')
            ->once()
            ->andReturn(['success' => false, 'message' => 'AJAX deletion failed.']);

        // AJAX request
        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ])->json('DELETE', route('admin.setting.category.destroy', $category->id));
        $response->assertStatus(500)
            ->assertJson(['success' => false, 'message' => 'AJAX deletion failed.']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}