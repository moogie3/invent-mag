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
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }
            return redirect()->route('admin.setting.category')->withErrors($validator)->withInput();
        }

        $result = $this->categoryService->createCategory($request->except('_token'));

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return redirect()->route('admin.setting.category')->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category created successfully.']);
        }
        return redirect()->route('admin.setting.category')->with('success', 'Category created');
    }

    public function update(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }
            return redirect()->route('admin.setting.category')->withErrors($validator)->withInput();
        }

        $category = Categories::find($id);
        if (!$category) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
            }
            return redirect()->route('admin.setting.category')->with('error', 'Category not found.');
        }

        $result = $this->categoryService->updateCategory($category, $request->except('_token'));

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return redirect()->route('admin.setting.category')->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        }
        return redirect()->route('admin.setting.category')->with('success', 'Category updated');
    }

    public function destroy(Request $request, $id)
    {
        $category = Categories::find($id);
        $result = $this->categoryService->deleteCategory($category);

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }
            return redirect()->route('admin.setting.category')->with('error', $result['message']);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
        }
        return redirect()->route('admin.setting.category')->with('success', 'Category deleted');
    }
}
