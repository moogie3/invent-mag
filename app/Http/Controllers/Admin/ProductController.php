<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(){
        $products = Product::with(['category', 'supplier' ,'unit'])->get();
        return view('admin.product.index', ['products' => $products]);
    }


    public function create()
    {
        $products = Product::all();
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        return view('admin.product.product-create', compact('products', 'units', 'suppliers','categories'));
    }

    public function edit($id)
        {
    $products = Product::find($id);
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        return view('admin.product.product-edit', compact('products', 'categories', 'units', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
        'code' => 'required|string',
        'name' => 'required|string',
        'quantity' => 'required|integer',
        'price' => 'required|numeric',
        'selling_price' => 'required|numeric',
        'category_id' => 'required|integer',
        'units_id' => 'required|integer',
        'supplier_id' => 'required|integer',
        'description' => 'nullable|string',
        'image' => 'required|image|mimes:jpeg,jpg,png'
        ]);

        $data = $request->except('_token');

        $isProductExist = Product::where('name', $request->name)->exists();
        if ($isProductExist) {
            return back()->withErrors([
                'name' => 'This product already exists'
            ])->withInput();
        }

        $images = $request->image;
        $originalImagesName= Str::random(10) . $images->getClientOriginalName();
        $images->storeAs("/image", $originalImagesName);
        $data['image'] = $originalImagesName;

        Product::create($data);

        return redirect()->route('admin.product')->with('success', 'Product created');
    }

    public function update(Request $request, $id){
        $products = Product::find($id);

        $request->validate([
        'code' => 'string',
        'name' => 'string',
        'quantity' => 'integer',
        'price' => 'numeric',
        'selling_price' => 'numeric',
        'category_id' => 'integer',
        'units_id' => 'integer',
        'supplier_id' => 'integer',
        'description' => 'nullable|string',
        'image' => 'image|mimes:jpeg,jpg,png'
        ]);

        $data = $request->except(["_token"]);
        $data = array_merge($products->toArray(), $data);

        if ($request->image) {
            $images = $request->image;
            $originalImagesName = Str::random(10) . $images->getClientOriginalName();
            $images->storeAs("/image", $originalImagesName);
            $data['image'] = $originalImagesName;

            Storage::delete('/image' . $products->image);
        }

        if (!empty($data)) {
            $products->update($data);
        }

        return redirect()->route('admin.product')->with('success', 'Product updated');
    }

    public function destroy($id)
    {
        Product::find($id)->delete();

        return redirect()->route('admin.product')->with('success', 'Product deleted');
    }
}