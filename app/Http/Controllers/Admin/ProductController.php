<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $totalcategory = Categories::count();
        return view('admin.product.index', compact('totalcategory', 'products', 'categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse', 'entries', 'totalproduct', 'lowStockCount', 'lowStockProducts', 'expiringSoonCount', 'expiringSoonProducts'));
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

    public function modalView($id)
    {
        try {
            $product = Product::with(['category', 'supplier', 'unit', 'warehouse'])->findOrFail($id);

            $product->formatted_price = \App\Helpers\CurrencyHelper::format($product->price);
            $product->formatted_selling_price = \App\Helpers\CurrencyHelper::format($product->selling_price);

            // Format dates if needed
            if ($product->has_expiry && $product->expiry_date) {
                $product->expiry_date = $product->expiry_date->format('Y-m-d');
            }

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'error' => 'Product not found',
                    'message' => $e->getMessage(),
                ],
                404,
            );
        }
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
            'warehouse_id' => 'nullable|integer',
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
            'has_expiry' => 'nullable|sometimes|boolean',
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

    /**
     * Bulk delete products
     */
    public function bulkDelete(Request $request)
    {
        try {
            // Log the incoming request for debugging
            Log::info('Bulk delete products request received', [
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            // Validate the incoming request
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:products,id',
            ]);

            $ids = $request->ids;
            $deletedCount = 0;
            $imagesDeleted = 0;

            Log::info('Validation passed, proceeding with deletion', [
                'ids' => $ids,
                'count' => count($ids),
            ]);

            // Use database transaction for data integrity
            DB::transaction(function () use ($ids, &$deletedCount, &$imagesDeleted) {
                // First, get all products to be deleted for logging and image cleanup
                $products = Product::whereIn('id', $ids)->get();

                if ($products->isEmpty()) {
                    throw new \Exception('No products found with the provided IDs');
                }

                Log::info('Found products to delete', [
                    'found_count' => $products->count(),
                    'product_codes' => $products->pluck('code')->toArray(),
                ]);

                // Log the deletion attempt
                Log::info('Bulk delete products initiated', [
                    'user_id' => Auth::id(),
                    'product_ids' => $ids,
                    'product_count' => count($ids),
                ]);

                // Delete associated images first
                foreach ($products as $product) {
                    if (!empty($product->image)) {
                        $imagePath = 'public/image/' . basename($product->image);

                        if (Storage::exists($imagePath)) {
                            Storage::delete($imagePath);
                            $imagesDeleted++;

                            Log::info('Deleted product image', [
                                'product_id' => $product->id,
                                'product_code' => $product->code,
                                'image_path' => $imagePath,
                            ]);
                        }
                    }
                }

                // Delete the products
                $deletedCount = Product::whereIn('id', $ids)->delete();
                Log::info("Successfully deleted {$deletedCount} products");
            });

            // Return success response with detailed information
            $response = [
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} product(s)",
                'deleted_count' => $deletedCount,
                'images_deleted' => $imagesDeleted,
                'details' => [
                    'products_deleted' => $deletedCount,
                    'images_cleaned' => $imagesDeleted,
                ],
            ];

            Log::info('Bulk delete completed successfully', $response);

            return response()->json($response);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Bulk delete validation failed', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid data provided',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Bulk delete products failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error deleting products. Please try again.',
                    'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ],
                500,
            );
        }
    }

    /**
     * Bulk export products to CSV
     */
    public function bulkExport(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:products,id',
            ]);

            $ids = $request->ids;

            // Get products with related data
            $products = Product::with(['category', 'supplier', 'unit', 'warehouse'])
                ->whereIn('id', $ids)
                ->get();

            // Create CSV content
            $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($products) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, ['Code', 'Name', 'Category', 'Supplier', 'Unit', 'Warehouse', 'Stock Quantity', 'Low Stock Threshold', 'Price', 'Selling Price', 'Has Expiry', 'Expiry Date', 'Description']);

                // CSV Data
                foreach ($products as $product) {
                    fputcsv($file, [$product->code, $product->name, $product->category->name ?? 'N/A', $product->supplier->name ?? 'N/A', $product->unit->name ?? 'N/A', $product->warehouse->name ?? 'N/A', $product->stock_quantity, $product->low_stock_threshold ?? 'N/A', $product->price, $product->selling_price, $product->has_expiry ? 'Yes' : 'No', $product->expiry_date ?? 'N/A', $product->description ?? 'N/A']);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error exporting products: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk update stock quantities with enhanced error handling
     */
    public function bulkUpdateStock(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'updates' => 'required|array|min:1',
                'updates.*.id' => 'required|integer|exists:products,id',
                'updates.*.stock_quantity' => 'required|integer|min:0',
                'updates.*.original_stock' => 'sometimes|integer|min:0',
            ]);

            $updates = $request->updates;
            $updatedCount = 0;
            $stockChanges = [];

            Log::info('Bulk stock update initiated', [
                'user_id' => Auth::id(),
                'updates_count' => count($updates),
            ]);

            // Use database transaction for data integrity
            DB::transaction(function () use ($updates, &$updatedCount, &$stockChanges) {
                foreach ($updates as $update) {
                    $product = Product::findOrFail($update['id']);
                    $originalStock = $product->stock_quantity;
                    $newStock = $update['stock_quantity'];

                    // Only update if there's actually a change
                    if ($originalStock != $newStock) {
                        $product->update([
                            'stock_quantity' => $newStock,
                        ]);

                        $updatedCount++;

                        $stockChanges[] = [
                            'product_id' => $product->id,
                            'product_code' => $product->code,
                            'original_stock' => $originalStock,
                            'new_stock' => $newStock,
                            'change' => $newStock - $originalStock,
                        ];
                    }
                }
            });

            Log::info('Bulk stock update completed', [
                'user_id' => Auth::id(),
                'updated_count' => $updatedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated stock for {$updatedCount} product(s)",
                'updated_count' => $updatedCount,
                'changes' => $stockChanges,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Bulk stock update validation failed', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid data provided',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Bulk stock update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error updating stock quantities. Please try again.',
                    'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ],
                500,
            );
        }
    }

    public function search(Request $request)
{
    try {
        // Log the incoming request for debugging
        Log::info('Product search request', [
            'query' => $request->get('q'),
            'method' => $request->method(),
            'user_id' => Auth::id()
        ]);

        $query = trim($request->get('q', ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
                'products' => [],
                'count' => 0
            ]);
        }

        // Build the search query with proper error handling
        $productsQuery = Product::query();

        // Add eager loading with null checks
        $productsQuery->with([
            'category' => function($q) {
                $q->select('id', 'name');
            },
            'unit' => function($q) {
                $q->select('id', 'name', 'symbol');
            },
            'supplier' => function($q) {
                $q->select('id', 'name');
            }
        ]);

        // Apply search filters
        $productsQuery->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('code', 'LIKE', "%{$query}%");

            // Search in category name with existence check
            $q->orWhereHas('category', function ($cat) use ($query) {
                $cat->where('name', 'LIKE', "%{$query}%");
            });

            // Search in supplier name with existence check
            $q->orWhereHas('supplier', function ($sup) use ($query) {
                $sup->where('name', 'LIKE', "%{$query}%");
            });
        });

        // Order and limit results
        $products = $productsQuery
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get();

        Log::info('Product search results', [
            'query' => $query,
            'count' => $products->count()
        ]);

        // Format products for frontend with proper null handling
        $formattedProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name ?? 'Unknown Product',
                'code' => $product->code ?? null,
                'stock_quantity' => $product->stock_quantity ?? 0,
                'low_stock_threshold' => $product->low_stock_threshold ?? 0,
                'image' => $product->image ? asset($product->image) : null,
                'price' => $product->price ?? 0,
                'selling_price' => $product->selling_price ?? 0,
                'has_expiry' => $product->has_expiry ?? false,
                'expiry_date' => $product->expiry_date ? $product->expiry_date->format('Y-m-d') : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'unit' => $product->unit ? [
                    'id' => $product->unit->id,
                    'name' => $product->unit->name,
                    'symbol' => $product->unit->symbol
                ] : null,
                'supplier' => $product->supplier ? [
                    'id' => $product->supplier->id,
                    'name' => $product->supplier->name
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $formattedProducts,
            'count' => $products->count(),
            'query' => $query,
            'message' => $products->count() > 0 ? 'Products found' : 'No products found'
        ]);

    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Database error in product search', [
            'error' => $e->getMessage(),
            'sql' => $e->getSql() ?? 'N/A',
            'bindings' => $e->getBindings() ?? [],
            'query' => $request->get('q')
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Database error occurred while searching',
            'error' => config('app.debug') ? $e->getMessage() : 'Database query failed',
            'products' => [],
            'count' => 0
        ], 500);

    } catch (\Exception $e) {
        Log::error('General error in product search', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'query' => $request->get('q')
        ]);

        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred while searching',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            'products' => [],
            'count' => 0
        ], 500);
    }
}
}
