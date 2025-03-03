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
        $entries = $request->input('entries', 10);//pagination
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
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $data = $request->except('_token','image');

        $isProductExist = Product::where('name', $request->name)->exists();
        if ($isProductExist) {
            return back()
            ->withErrors([
                'name' => 'This product already exists',
            ])
            ->withInput();
        }

        if ($request->hasFile('image')) {
            $images = $request->file('image'); // use file() to get the uploaded file
            $originalImagesName = Str::random(10) . '_' . $images->getClientOriginalName();
            $images->storeAs('public/image', $originalImagesName);
            $data['image'] = $originalImagesName;
        } else {
            $data['image'] = null;
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
        'quantity' => 'integer',
        'price' => 'numeric',
        'selling_price' => 'numeric',
        'category_id' => 'integer',
        'units_id' => 'integer',
        'supplier_id' => 'integer',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,jpg,png',
    ]);

    // Get all fields except image
    $data = $request->except(['_token', 'image']);

    // If a new image is uploaded, process and save it
    if ($request->hasFile('image')) {
        // Check if the old image exists
        if (!empty($products->image)) {
            $oldImagePath = 'public/image/' . $products->image;
            if (Storage::exists($oldImagePath)) {
                Storage::delete($oldImagePath);
            }
        }

        // Upload new image
        $image = $request->file('image');
        $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
        $image->storeAs('public/image', $imageName); // store in storage/app/public/image/

        $data['image'] = $imageName; // save new image
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
