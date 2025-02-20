<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request) {
        $entries = $request->input('entries', 10);
        $categories = Categories::paginate($entries);
        return view('admin.category.index', compact('categories', 'entries'));
    }


    public function create(){
        $categories = Categories::all();
        return view('admin.category.category-create', compact('categories'));
    }

    public function edit($id)
    {
        $categories = Categories::find($id);
        return view('admin.category.category-edit', ['categories' => $categories]);
    }

    public function store(Request $request){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $isCategoryExist = Categories::where('name', $request->name)->exists();

        if ($isCategoryExist) {
            return back()
            ->withErrors([
                'name' => 'This category already exist'
            ])

            ->withInput();
        }

        Categories::create($data);

        return redirect()->route('admin.category')->with('success','Category created');
    }

    public function update(Request $request, $id){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $categories = Categories::find($id);
        $categories->update($data);
        return redirect()->route('admin.category')->with('success', 'Category updated');
    }

    public function destroy($id)
    {
        Categories::find($id)->delete();

        return redirect()->route('admin.category')->with('success', 'Category deleted');
    }
}