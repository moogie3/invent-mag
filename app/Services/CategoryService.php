<?php

namespace App\Services;

use App\Models\Categories;

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
        if (Categories::where('name', $data['name'])->exists()) {
            return ['success' => false, 'message' => 'This category already exists.'];
        }

        Categories::create($data);

        return ['success' => true, 'message' => 'Category created successfully.'];
    }

    public function updateCategory(Categories $category, array $data)
    {
        $category->update($data);

        return ['success' => true, 'message' => 'Category updated successfully.'];
    }

    public function deleteCategory(Categories $category)
    {
        $category->delete();

        return ['success' => true, 'message' => 'Category deleted successfully.'];
    }
}