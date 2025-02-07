<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
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

    public function store(Request $request)
{
    // Validate the data
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
    $originalImage= Str::random(10) . $images->getClientOriginalName();
    $images->storeAs("/image", $originalImage);
    $data['image'] = $originalImage;

    Product::create($data);

    return redirect()->route('admin.product')->with('success', 'Product created');
}


}