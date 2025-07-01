<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request) {
        $entries = $request->input('entries', 10);//pagination
        $categories = Categories::paginate($entries);
        $totalcategory = Categories::count();
        return view('admin.category.index', compact('categories', 'entries','totalcategory'));
    }

    public function store(Request $request){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $isCategoryExist = Categories::where('name', $request->name)->exists();

        if ($isCategoryExist) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'This category already exists.', 'errors' => ['name' => ['This category already exists.']]], 422);
            }
            return back()
            ->withErrors([
                'name' => 'This category already exist'
            ])

            ->withInput();
        }

        Categories::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category created successfully.']);
        }
        return redirect()->route('admin.setting.category')->with('success','Category created');
    }

    public function update(Request $request, $id){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $categories = Categories::find($id);
        $categories->update($data);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        }
        return redirect()->route('admin.setting.category')->with('success', 'Category updated');
    }

    public function destroy($id)
    {
        Categories::find($id)->delete();

        return redirect()->route('admin.setting.category')->with('success', 'Category deleted');
    }
}