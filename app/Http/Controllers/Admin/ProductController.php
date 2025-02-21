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
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $products = Product::with(['category', 'supplier', 'unit'])->paginate($entries);
        $totalproduct = Product::count();
        return view('admin.product.index', compact('products', 'entries', 'totalproduct'));
    }

    public function create()
    {
        $products = Product::all();
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        return view('admin.product.product-create', compact('products', 'units', 'suppliers', 'categories'));
    }

    public function edit($id)
    {
        $products = Product::find($id);
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        return view('admin.product.product-edit', compact('products', 'categories', 'units', 'suppliers','productsr'));
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
            'image' => 'required|image|mimes:jpeg,jpg,png',
        ]);

        $data = $request->except('_token');

        $isProductExist = Product::where('name', $request->name)->exists();
        if ($isProductExist) {
            return back()
                ->withErrors([
                    'name' => 'This product already exists',
                ])
                ->withInput();
        }

        $images = $request->image;
        $originalImagesName = Str::random(10) . $images->getClientOriginalName();
        $images->storeAs('/image', $originalImagesName);
        $data['image'] = $originalImagesName;

        Product::create($data);

        return redirect()->route('admin.product')->with('success', 'Product created');
    }

    public function update(Request $request, $id)
    {
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
            'image' => 'image|mimes:jpeg,jpg,png',
        ]);

        $data = $request->except(['_token', 'image']); // Exclude image field

        // If a new image is uploaded, update it; otherwise, keep the old one
        if ($request->hasFile('image')) {
            $images = $request->file('image');
            $originalImagesName = Str::random(10) . $images->getClientOriginalName();
            $images->storeAs('image', $originalImagesName); // Store new image

            // Delete old image
            if ($products->image) {
                Storage::delete('image/' . $products->image);
            }

            $data['image'] = $originalImagesName; // Set new image name
        } else {
            $data['image'] = $products->image; // Keep the old image
        }

        $products->update($data);

        return redirect()->route('admin.product')->with('success', 'Product updated');
    }

    public function destroy($id)
    {
        Product::find($id)->delete();

        return redirect()->route('admin.product')->with('success', 'Product deleted');
    }
}