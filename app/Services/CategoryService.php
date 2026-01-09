<?php

namespace App\Services;

use App\Models\Categories;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryService
{
    public function getCategoryIndexData(int $entries)
    {
        $categories = Categories::paginate($entries);
        $totalcategory = Categories::count();
        return compact('categories', 'entries', 'totalcategory');
    }

    public function createCategory(array $data)
    {
        Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('tenant_id', auth()->user()->tenant_id),
            ],
            // Add other validation rules here if needed
        ])->validate();
        
        $category = Categories::create($data);

        return ['success' => true, 'message' => 'Category created successfully.', 'category' => $category];
    }

    public function updateCategory(Categories $category, array $data)
    {
        $category->update($data);

        return ['success' => true, 'message' => 'Category updated successfully.', 'category' => $category];
    }

    public function deleteCategory(Categories $category)
    {
        $category->delete();

        return ['success' => true, 'message' => 'Category deleted successfully.'];
    }
}