<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Categories;
use Illuminate\Http\Request;

/**
 * @group Categories
 *
 * APIs for managing categories
 */
class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @queryParam per_page int The number of categories to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $categories = Categories::with('parent')->paginate($perPage);
        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @response 201 scenario="Success"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Categories::create($validated);

        return new CategoryResource($category);
    }

    /**
     * Display the specified category.
     *
     * @urlParam category required The ID of the category. Example: 1
     */
    public function show(Categories $category)
    {
        return new CategoryResource($category->load('parent', 'children'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @response 200 scenario="Success"
     */
    public function update(Request $request, Categories $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category->update($validated);

        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Categories $category)
    {
        $category->delete();

        return response()->noContent();
    }
}
