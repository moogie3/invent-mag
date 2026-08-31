<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Helpers\ProductHelper;
use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        try {
            $entries = $request->input('entries', 10);
            
            $filters = $request->only(['warehouse_id']);
            
            // Check if warehouse_id was explicitly provided in request
            $warehouseIdProvided = $request->has('warehouse_id');
            
            // Only apply warehouse filter if explicitly provided and not empty
            // Default is "All Warehouses" (no filter)
            if ($warehouseIdProvided && $filters['warehouse_id']) {
                // Filter is already set from request
            } else {
                // No warehouse filter - show all warehouses
                unset($filters['warehouse_id']);
            }

            // Pass filters to service
            $products = $this->productService->getPaginatedProducts($entries, $filters);

            $totalproduct = Product::count();
            $expiringSoonCount = $this->productService->getExpiringSoonPOItemsCount();
            $totalcategory = Categories::count();

            $formData = $this->productService->getProductFormData();

            $lowStockProducts = Product::getLowStockProducts();
            $lowStockProductsPerWarehouse = Product::getLowStockProductsPerWarehouse();
            $expiringSoonProducts = $this->productService->getExpiringSoonPOItems();

            // Get selected warehouse if warehouse_id is provided AND not empty
            $selectedWarehouse = null;
            if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
                $selectedWarehouse = Warehouse::find($filters['warehouse_id']);
            }

            // Calculate TOTAL low stock count across ALL warehouses (for header)
            // This shows overall low stock status regardless of filter
            $lowStockCount = Product::lowStockCountAllWarehouses();

            return view('admin.product.index', compact('totalcategory', 'products', 'entries', 'totalproduct', 'lowStockCount', 'lowStockProducts', 'lowStockProductsPerWarehouse', 'expiringSoonCount', 'expiringSoonProducts', 'selectedWarehouse') + $formData);
        } catch (\Exception $e) {
            Log::error('Failed to load products index: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Error loading products. Please try again.');
        }
    }

    public function create()
    {
        try {
            $formData = $this->productService->getProductFormData();

            return view('admin.product.product-create', $formData);
        } catch (\Exception $e) {
            Log::error('Failed to load product create form: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('admin.product')->with('error', 'Error loading form. Please try again.');
        }
    }

    public function modalView($id)
    {
        try {
            $thirtyDaysFromNow = now()->addDays(30);
            $mainWarehouse = Warehouse::where('is_main', true)->first();
            $product = Product::with([
                'category',
                'supplier',
                'unit',
                'warehouses',
                'productWarehouses' => function ($query) use ($mainWarehouse) {
                    if ($mainWarehouse) {
                        $query->where('warehouse_id', $mainWarehouse->id);
                    }
                },
                'poItems' => function ($query) use ($thirtyDaysFromNow) {
                    $query->whereNotNull('expiry_date')
                        ->where('expiry_date', '>=', now())
                        ->where('expiry_date', '<=', $thirtyDaysFromNow)
                        ->where('remaining_quantity', '>', 0)
                        ->with('purchaseOrder.warehouse')
                        ->orderBy('expiry_date', 'asc');
                },
            ])->findOrFail($id);

            $product->setAppends(['main_warehouse_stock']);
            if ($mainWarehouse) {
                $mainStock = $product->productWarehouses->first();
                $product->main_warehouse_stock = $mainStock ? (float) $mainStock->quantity : 0;
            } else {
                $product->main_warehouse_stock = 0;
            }

            $product->formatted_price = CurrencyHelper::formatWithPosition($product->price);
            $product->formatted_selling_price = CurrencyHelper::formatWithPosition($product->selling_price);

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Product not found',
                'message' => $e->getMessage(),
            ], 404);
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
        $lowStockProducts = Product::getLowStockProducts();

        return view('admin.product.product-edit', compact('lowStockProducts', 'products', 'categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('products', 'code')->where('tenant_id', auth()->user()->tenant_id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('tenant_id', auth()->user()->tenant_id)],
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'units_id' => 'required|integer|exists:units,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'has_expiry' => 'sometimes|boolean',
        ]);

        try {
            $this->productService->createProduct($request->all());

            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully',
                    'redirect' => route('admin.product'),
                ]);
            }

            return redirect()->route('admin.product')->with('success', 'Product created');
        } catch (ValidationException $e) {
            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Product creation failed: '.$e->getMessage(), ['exception' => $e, 'input' => $request->except('image')]);

            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating product. Please try again.',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating product. Please try again.');
        }
    }

    /**
     * @group Products
     *
     * @summary Quickly Create a Product
     *
     * @bodyParam code string required The product code. Example: "PROD-001"
     * @bodyParam name string required The product name. Example: "New Product"
     * @bodyParam stock_quantity integer required The initial stock quantity. Example: 100
     * @bodyParam price number required The purchase price of the product. Example: 99.99
     * @bodyParam selling_price number required The selling price of the product. Example: 149.99
     * @bodyParam category_id integer required The ID of the product category. Example: 1
     * @bodyParam units_id integer required The ID of the product unit. Example: 1
     * @bodyParam supplier_id integer required The ID of the product supplier. Example: 1
     *
     * @response 200 {"success": true, "message": "Product created successfully", "product": {"id": 1, "name": "New Product"}}
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', Rule::unique('products', 'code')->where('tenant_id', auth()->user()->tenant_id)],
            'name' => ['required', 'string', Rule::unique('products', 'name')->where('tenant_id', auth()->user()->tenant_id)],
            'barcode' => 'nullable|string',
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'category_id' => 'required|integer|exists:categories,id',
            'units_id' => 'required|integer|exists:units,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'has_expiry' => 'nullable|sometimes|boolean',
        ]);

        try {
            $product = $this->productService->quickCreateProduct($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('products', 'code')->where('tenant_id', auth()->user()->tenant_id)->ignore($product->id)],
            'barcode' => 'nullable|string|max:100|unique:products,barcode,'.$product->id,
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('tenant_id', auth()->user()->tenant_id)->ignore($product->id)],
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'units_id' => 'required|integer|exists:units,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'has_expiry' => 'sometimes|boolean',
        ]);

        try {
            $this->productService->updateProduct($product, $request->all());

            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'redirect' => route('admin.product'),
                ]);
            }

            return redirect()->route('admin.product')->with('success', 'Product updated successfully');
        } catch (ValidationException $e) {
            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Product update failed for ID '.$id.': '.$e->getMessage(), ['exception' => $e]);

            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating product. Please try again.',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating product. Please try again.');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $this->productService->deleteProduct($product);

            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product deleted successfully',
                ]);
            }

            return redirect()->route('admin.product')->with('success', 'Product deleted');
        } catch (\Exception $e) {
            if ($request->header('X-Laravel-JSON')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting product. Please try again.',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error deleting product. Please try again.');
        }
    }

    public function getProductMetrics()
    {
        $totalproduct = Product::count();
        $totalcategory = Categories::count();
        $lowStockCount = Product::lowStockCount();

        return response()->json([
            'totalproduct' => $totalproduct,
            'totalcategory' => $totalcategory,
            'lowStockCount' => $lowStockCount,
        ]);
    }

    /**
     * @group Products
     *
     * @summary Bulk Delete Products
     *
     * @bodyParam ids array required An array of product IDs to delete. Example: [1, 2, 3]
     *
     * @response 200 {"success": true, "message": "Successfully deleted 2 product(s)", "deleted_count": 2, "images_deleted": 1}
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        try {
            $result = $this->productService->bulkDeleteProducts($request->ids);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$result['deleted_count']} product(s)",
                'deleted_count' => $result['deleted_count'],
                'images_deleted' => $result['images_deleted'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting products. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @group Products
     *
     * @summary Bulk Export Products
     *
     * @bodyParam ids array required An array of product IDs to export. Example: [1, 2, 3]
     * @bodyParam export_option string required The export format ('pdf' or 'csv'). Example: "csv"
     *
     * @response 200 "The exported file."
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $file = $this->productService->bulkExportProducts($request->all(), $request->ids, $request->export_option);

            return $file;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting products. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function bulkUpdateStock(Request $request)
    {
        $request->validate([
            'updates' => 'required|array|min:1',
            'updates.*.id' => 'required|integer|exists:products,id',
            'updates.*.stock_quantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:500',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        try {
            $result = $this->productService->bulkUpdateStock(
                $request->updates,
                $request->reason,
                auth()->id(),
                $request->warehouse_id
            );
            $updatedProductsWithBadges = [];
            foreach ($result['changes'] as $change) {
                // Use the new total stock for badge calculation, not the warehouse-specific stock
                $lowStockThreshold = $change['low_stock_threshold'] ?? 10;
                [$badgeClass, $badgeText] = ProductHelper::getStockClassAndText($change['new_stock_quantity'], $lowStockThreshold);
                $updatedProductsWithBadges[] = array_merge($change, [
                    'badge_class' => $badgeClass,
                    'badge_text' => $badgeText,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully updated stock for {$result['updated_count']} product(s)",
                'updated_count' => $result['updated_count'],
                'changes' => $updatedProductsWithBadges,
                'warehouse_id' => $request->warehouse_id, // Return the warehouse ID that was updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating stock quantities. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'adjustment_amount' => 'required|numeric|min:1', // Can be positive or negative, validation for negative will be handled by adjustment_type
            'adjustment_type' => 'required|in:increase,decrease,correction',
            'reason' => 'nullable|string|max:500',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $adjustedProduct = $this->productService->adjustProductStock(
                $product,
                $request->adjustment_amount,
                $request->adjustment_type,
                $request->reason,
                auth()->id(), // Pass the authenticated user's ID
                $request->warehouse_id
            );

            // Prepare the response with both total and warehouse-specific stock
            $responseData = [
                'id' => $adjustedProduct->id,
                'total_stock' => $adjustedProduct->total_stock,
                'stock_quantity' => $adjustedProduct->total_stock, // For backward compatibility
                'low_stock_threshold' => $adjustedProduct->low_stock_threshold,
            ];

            if ($request->warehouse_id) {
                $warehousePivot = $adjustedProduct->warehouses()->find($request->warehouse_id);
                $responseData['warehouse_stock'] = $warehousePivot ? $warehousePivot->pivot->quantity : 0;
                $responseData['warehouse_id'] = $request->warehouse_id;
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'product' => $responseData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adjusting stock: '.$e->getMessage(),
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = trim($request->get('q', ''));

            if (empty($query)) {
                // If the query is empty, return all products
                $products = Product::all();
            } else {
                // Otherwise, perform the search
                $products = $this->productService->searchProducts($query);
            }

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching products.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getExpiringSoonProducts()
    {
        try {
            $expiringSoonProducts = $this->productService->getExpiringSoonPOItems();

            return response()->json($expiringSoonProducts);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching expiring products.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getAdjustmentLog($id)
    {
        try {
            $adjustments = StockAdjustment::where('product_id', $id)
                ->with('adjustedBy:id,name') // Eager load user name
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($adjustments);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching adjustment log.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function searchByBarcode(Request $request)
    {
        try {
            $request->validate(['barcode' => 'required|string']);
            $product = $this->productService->searchByBarcode($request->barcode);

            if ($product) {
                return response()->json($product);
            }

            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching by barcode.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
