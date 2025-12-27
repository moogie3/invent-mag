<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
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
        $this->middleware('permission:view-categories')->only(['index', 'show']);
        $this->middleware('permission:create-categories')->only(['store']);
        $this->middleware('permission:edit-categories')->only(['update']);
        $this->middleware('permission:delete-categories')->only(['destroy']);
    }
    /**
     * Display a listing of the categories.
     *
     * @group Categories
     * @authenticated
     * @queryParam per_page int The number of categories to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Electronics","description":"Electronic components and devices",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 201 scenario="Success" {"data":{"id":1,"name":"Electronics","description":"Electronic components and devices",...}}
     * @response 422 scenario="Validation Error" {"success":false,"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreCategoryRequest $request)
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Electronics","description":"Electronic components and devices",...}}
     * @response 404 scenario="Not Found" {"message": "Category not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Electronics (Updated)","description":"Updated description","created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T13:00:00.000000Z"}}
     * @response 404 scenario="Not Found" {"message": "Category not found."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateCategoryRequest $request, Categories $category)
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
     * @response 404 scenario="Not Found" {"message": "Category not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Categories $category)
    {
        $this->categoryService->deleteCategory($category);

        return response()->noContent();
    }
}