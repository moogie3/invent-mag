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
        $products = Product::all();
        return view ('admin..product.index', ['products' => $products]);
    }

    public function create()
    {
    $categories = Categories::all();
    $units = Unit::all();
    $suppliers = Supplier::all();

    return view('admin.product.product-create', compact('categories', 'units', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'quantity' => 'required|int',
            'price' => 'required|float',
            'selling_price' => 'required|float',
            'category_id' => 'int',
            'units_id' => 'int',
            'supplier_id' => 'int',
            'description' => 'string',
            'image' => 'required|image|mimes:jpeg,jpg,png'
        ]);

        $image = $request->image;

        $originalImage = Str::random(10) . $image->getClientOriginalName();

        $image->storeAs("public/item", $originalImage);

        $data['image'] = $originalImage;

        Product::create($data);

        return redirect()->route('admin.product.index')->with('success', 'Product created');
    }
}