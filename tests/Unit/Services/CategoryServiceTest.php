<?php

namespace Tests\Unit\Services;

use App\Models\Categories;
use App\Services\CategoryService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->user->assignRole('superuser'); // Ensure the user has permissions for services
        $this->categoryService = new CategoryService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(CategoryService::class, $this->categoryService);
    }

    #[Test]
    public function it_can_get_category_index_data()
    {
        Categories::factory()->count(5)->create();

        $entries = 3;
        $result = $this->categoryService->getCategoryIndexData($entries);

        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('totalcategory', $result);

        $this->assertCount($entries, $result['categories']);
        $this->assertEquals(5, $result['totalcategory']);
        $this->assertEquals($entries, $result['entries']);
    }

    #[Test]
    public function it_can_create_a_category_successfully()
    {
        $data = ['name' => 'Electronics', 'description' => 'Electronic gadgets and devices'];
        $result = $this->categoryService->createCategory($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Category created successfully.', $result['message']);
        $this->assertDatabaseHas('categories', ['name' => 'Electronics']);
    }

    #[Test]
    public function it_returns_error_if_category_already_exists()
    {
        Categories::factory()->create(['name' => 'Electronics', 'description' => 'Electronic gadgets and devices']);

        $data = ['name' => 'Electronics', 'description' => 'Another description'];
        $result = $this->categoryService->createCategory($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('This category already exists.', $result['message']);
        $this->assertDatabaseCount('categories', 1);
    }

    #[Test]
    public function it_can_update_a_category_successfully()
    {
        $category = Categories::factory()->create(['name' => 'Books', 'description' => 'Various books']);
        $updatedData = ['name' => 'Fiction Books', 'description' => 'Books of the fiction genre'];

        $result = $this->categoryService->updateCategory($category, $updatedData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Category updated successfully.', $result['message']);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Fiction Books', 'description' => 'Books of the fiction genre']);
    }

    #[Test]
    public function it_can_delete_a_category_successfully()
    {
        $category = Categories::factory()->create(['name' => 'Clothing', 'description' => 'Apparel and accessories']);

        $result = $this->categoryService->deleteCategory($category);

        $this->assertTrue($result['success']);
        $this->assertEquals('Category deleted successfully.', $result['message']);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
