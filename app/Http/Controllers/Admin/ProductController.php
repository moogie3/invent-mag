<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10); // Pagination
        $products = Product::with(['category', 'supplier', 'unit', 'warehouse'])->paginate($entries);
        $totalproduct = Product::count();
        $lowStockCount = Product::lowStockCount(); // Get count of low stock products
        $expiringSoonCount = Product::expiringSoonCount(); // Get count of expiring soon products
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        $lowStockProducts = Product::getLowStockProducts();
        $expiringSoonProducts = Product::getExpiringSoonProducts(); // Get expiring soon products

        return view('admin.product.index', compact('products', 'categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse', 'entries', 'totalproduct', 'lowStockCount', 'lowStockProducts', 'expiringSoonCount', 'expiringSoonProducts'));
    }

    public function create()
    {
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        return view('admin.product.product-create', compact('categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse'));
    }

    public function edit($id)
    {
        $products = Product::findOrFail($id);
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        return view('admin.product.product-edit', compact('products', 'categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'stock_quantity' => 'required|integer',
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'category_id' => 'required|integer',
            'units_id' => 'required|integer',
            'supplier_id' => 'required|integer',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
            'has_expiry' => 'sometimes|boolean',
            'expiry_date' => 'nullable|date|required_if:has_expiry,1',
        ]);

        $data = $request->except('_token', 'image');

        $data['has_expiry'] = $request->boolean('has_expiry');

        // If warehouse_id is not provided, use the main warehouse
        if (empty($data['warehouse_id'])) {
            $mainWarehouse = Warehouse::where('is_main', true)->first();
            if ($mainWarehouse) {
                $data['warehouse_id'] = $mainWarehouse->id;
            }
        }

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

    // Update the quickCreate method similarly:
    public function quickCreate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'stock_quantity' => 'required|integer',
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'category_id' => 'required|integer',
            'units_id' => 'required|integer',
            'supplier_id' => 'required|integer',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
            'has_expiry' => 'sometimes|boolean',
            'expiry_date' => 'nullable|date|required_if:has_expiry,1',
        ]);

        $data = $request->except('_token', 'image');

        // Set has_expiry to false if not provided
        $data['has_expiry'] = $request->boolean('has_expiry');

        // If warehouse_id is not provided, use the main warehouse
        if (empty($data['warehouse_id'])) {
            $mainWarehouse = Warehouse::where('is_main', true)->first();
            if ($mainWarehouse) {
                $data['warehouse_id'] = $mainWarehouse->id;
            }
        }

        // Clear expiry_date if has_expiry is false
        if (!$data['has_expiry']) {
            $data['expiry_date'] = null;
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);

            // Store only the relative path
            $data['image'] = $imageName;
        }

        $product = Product::create($data);

        // Load the relationships
        $product->load(['unit', 'warehouse']);

        // Add unit symbol to the product for the frontend
        $product->unit_symbol = $product->unit->symbol;
        $product->warehouse_name = $product->warehouse ? $product->warehouse->name : 'None';
        $product->image_url = $product->image ? asset('storage/image/' . $product->image) : asset('/images/default-product.png');

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product,
        ]);
    }

    // Update the update method to handle warehouse_id:
    public function update(Request $request, $id)
    {
        $products = Product::findOrFail($id);

        $request->validate([
            'code' => 'string',
            'name' => 'string',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'numeric',
            'selling_price' => 'numeric',
            'category_id' => 'integer',
            'units_id' => 'integer',
            'supplier_id' => 'integer',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
            'has_expiry' => 'sometimes|boolean',
            'expiry_date' => 'nullable|date|required_if:has_expiry,1',
        ]);

        $data = $request->except(['_token', 'image']);

        $data['has_expiry'] = $request->boolean('has_expiry');

        // If warehouse_id is not provided, use the main warehouse
        if (empty($data['warehouse_id'])) {
            $mainWarehouse = Warehouse::where('is_main', true)->first();
            if ($mainWarehouse) {
                $data['warehouse_id'] = $mainWarehouse->id;
            }
        }

        // Clear expiry_date if has_expiry is false
        if (!$data['has_expiry']) {
            $data['expiry_date'] = null;
        }
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