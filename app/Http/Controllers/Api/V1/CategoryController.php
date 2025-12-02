<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Categories;
use App\Services\CategoryService;
use Illuminate\Http\Request;

/**
 * @group Categories
 *
 * APIs for managing categories
 */
class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    /**
     * Display a listing of the categories.
     *
     * @group Categories
     * @authenticated
     * @queryParam per_page int The number of categories to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of categories.
     * @responseField data[].id integer The ID of the category.
     * @responseField data[].name string The name of the category.
     * @responseField data[].description string The description of the category.
     * @responseField data[].parent_id integer The ID of the parent category.
     * @responseField data[].created_at string The date and time the category was created.
     * @responseField data[].updated_at string The date and time the category was last updated.
     * @responseField links object Links for pagination.
     * @responseField links.first string The URL of the first page.
     * @responseField links.last string The URL of the last page.
     * @responseField links.prev string The URL of the previous page.
     * @responseField links.next string The URL of the next page.
     * @responseField meta object Metadata for pagination.
     * @responseField meta.current_page integer The current page number.
     * @responseField meta.from integer The starting number of the results on the current page.
     * @responseField meta.last_page integer The last page number.
     * @responseField meta.path string The URL path.
     * @responseField meta.per_page integer The number of results per page.
     * @responseField meta.to integer The ending number of the results on the current page.
     * @responseField meta.total integer The total number of results.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $data = $this->categoryService->getCategoryIndexData($perPage);
        return CategoryResource::collection($data['categories']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Categories
     * @authenticated
     * @bodyParam name string required The name of the category. Example: "Electronics"
     * @bodyParam description string The description of the category. Example: "Electronic components and devices"
     * @bodyParam parent_id int The ID of the parent category. Example: 1
     *
     * @responseField id integer The ID of the category.
     * @responseField name string The name of the category.
     * @responseField description string The description of the category.
     * @responseField parent_id integer The ID of the parent category.
     * @responseField created_at string The date and time the category was created.
     * @responseField updated_at string The date and time the category was last updated.
     * @response 422 scenario="Creation Failed" {"success": false, "message": "Failed to create category."}
     */
    public function store(\App\Http\Requests\Api\V1\StoreCategoryRequest $request)
    {
        $result = $this->categoryService->createCategory($request->validated());

        return new CategoryResource($result['category']);
    }

    /**
     * Display the specified category.
     *
     * @group Categories
     * @authenticated
     * @urlParam category required The ID of the category. Example: 1
     *
     * @responseField id integer The ID of the category.
     * @responseField name string The name of the category.
     * @responseField description string The description of the category.
     * @responseField parent_id integer The ID of the parent category.
     * @responseField created_at string The date and time the category was created.
     * @responseField updated_at string The date and time the category was last updated.
     * @responseField parent object The parent category.
     * @responseField children object[] A list of child categories.
     */
    public function show(Categories $category)
    {
        return new CategoryResource($category->load('parent', 'children'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Categories
     * @authenticated
     * @urlParam category required The ID of the category. Example: 1
     * @bodyParam name string required The name of the category. Example: "Electronics"
     * @bodyParam description string The description of the category. Example: "Electronic components and devices"
     * @bodyParam parent_id int The ID of the parent category. Example: 1
     *
     * @responseField id integer The ID of the category.
     * @responseField name string The name of the category.
     * @responseField description string The description of the category.
     * @responseField parent_id integer The ID of the parent category.
     * @responseField created_at string The date and time the category was created.
     * @responseField updated_at string The date and time the category was last updated.
     * @response 422 scenario="Update Failed" {"success": false, "message": "Failed to update category."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdateCategoryRequest $request, Categories $category)
    {
        $result = $this->categoryService->updateCategory($category, $request->validated());

        return new CategoryResource($result['category']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Categories
     * @authenticated
     * @urlParam category required The ID of the category. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Categories $category)
    {
        $this->categoryService->deleteCategory($category);

        return response()->noContent();
    }
}