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
        $entries = $request->input('entries', 10); // Pagination
        $products = Product::with(['category', 'supplier', 'unit'])->paginate($entries);
        $totalproduct = Product::count();
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        return view('admin.product.index', compact('products','categories','units','suppliers', 'entries', 'totalproduct'));
    }

    public function create()
    {
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        return view('admin.product.product-create', compact('categories', 'units', 'suppliers'));
    }

    public function edit($id)
    {
        $products = Product::findOrFail($id);
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
            'stock_quantity' => 'required|integer',
            'price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'category_id' => 'required|integer',
            'units_id' => 'required|integer',
            'supplier_id' => 'required|integer',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $data = $request->except('_token', 'image');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);

            // Store only the relative path
            $data['image'] = $imageName;
        }

        Product::create($data);

        return redirect()->route('admin.product')->with('success', 'Product created');
    }

    public function update(Request $request, $id)
    {
        $products = Product::findOrFail($id);

        $request->validate([
            'code' => 'string',
            'name' => 'string',
            'stock_quantity' => 'integer',
            'price' => 'numeric',
            'selling_price' => 'numeric',
            'category_id' => 'integer',
            'units_id' => 'integer',
            'supplier_id' => 'integer',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $data = $request->except(['_token', 'image']);

        // Check if a new image is uploaded
        if ($request->hasFile('image')) {
            // Delete old image if exists
            $oldImagePath = 'public/image/' . basename($products->image); // Use only filename

            if (!empty($products->image) && Storage::exists($oldImagePath)) {
                Storage::delete($oldImagePath);
            }

            // Upload new image
            $image = $request->file('image');
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);

            // Store only the image filename, NOT the full URL
            $data['image'] = $imageName;
        }

        $products->update($data);

        return redirect()->route('admin.product')->with('success', 'Product updated successfully');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete image from storage if exists
        if (!empty($product->image)) {
            Storage::delete('public/' . $product->image);
        }

        $product->delete();

        return redirect()->route('admin.product')->with('success', 'Product deleted');
    }
}