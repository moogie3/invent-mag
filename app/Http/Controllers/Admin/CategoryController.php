<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $data = $this->categoryService->getCategoryIndexData($entries);
        return view('admin.category.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $result = $this->categoryService->createCategory($request->except('_token'));

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category created successfully.']);
        }
        return redirect()->route('admin.setting.category')->with('success', 'Category created');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $category = Categories::find($id);
        $result = $this->categoryService->updateCategory($category, $request->except('_token'));

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        }
        return redirect()->route('admin.setting.category')->with('success', 'Category updated');
    }

    public function destroy($id)
    {
        $category = Categories::find($id);
        $this->categoryService->deleteCategory($category);

        return redirect()->route('admin.setting.category')->with('success', 'Category deleted');
    }
}
