<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Http\Request;

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
            $filters = $request->only(['warehouse_id']); // Add other filters if needed
            
            // Pass filters to service
            $products = $this->productService->getPaginatedProducts($entries, $filters);
            
            $totalproduct = Product::count();
            $lowStockCount = Product::lowStockCount();
            $expiringSoonCount = $this->productService->getExpiringSoonPOItemsCount();
            $totalcategory = Categories::count();

            $formData = $this->productService->getProductFormData();

            $lowStockProducts = Product::getLowStockProducts();
            $expiringSoonProducts = $this->productService->getExpiringSoonPOItems();

            // Get selected warehouse if warehouse_id is provided
            $selectedWarehouse = null;
            if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
                $selectedWarehouse = Warehouse::find($filters['warehouse_id']);
            }

            return view('admin.product.index', compact('totalcategory', 'products', 'entries', 'totalproduct', 'lowStockCount', 'lowStockProducts', 'expiringSoonCount', 'expiringSoonProducts', 'selectedWarehouse') + $formData);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load products index: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Error loading products. Please try again.');
        }
    }

    public function create()
    {
        try {
            $formData = $this->productService->getProductFormData();
            return view('admin.product.product-create', $formData);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load product create form: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('admin.product')->with('error', 'Error loading form. Please try again.');
        }
    }

    public function modalView($id)
    {
        try {
            $product = Product::with(['category', 'supplier', 'unit', 'warehouses', 'poItems' => function($query) {
                $query->where('remaining_quantity', '>', 0)->orderBy('expiry_date', 'asc');
            }])->findOrFail($id);
            $product->formatted_price = \App\Helpers\CurrencyHelper::formatWithPosition($product->price);
            $product->formatted_selling_price = \App\Helpers\CurrencyHelper::formatWithPosition($product->selling_price);
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
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0', // Optional opening stock
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'units_id' => 'required|integer|exists:units,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id', // For initial stock placement
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'has_expiry' => 'sometimes|boolean',
        ]);

        try {
            $this->productService->createProduct($request->all());
            return redirect()->route('admin.product')->with('success', 'Product created');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Product creation failed: ' . $e->getMessage(), ['exception' => $e, 'input' => $request->except('image')]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating product. Please try again.');
        }
    }

    /**
     * @group Products
     * @summary Quickly Create a Product
     * @bodyParam code string required The product code. Example: "PROD-001"
     * @bodyParam name string required The product name. Example: "New Product"
     * @bodyParam stock_quantity integer required The initial stock quantity. Example: 100
     * @bodyParam price number required The purchase price of the product. Example: 99.99
     * @bodyParam selling_price number required The selling price of the product. Example: 149.99
     * @bodyParam category_id integer required The ID of the product category. Example: 1
     * @bodyParam units_id integer required The ID of the product unit. Example: 1
     * @bodyParam supplier_id integer required The ID of the product supplier. Example: 1
     * @response 200 {"success": true, "message": "Product created successfully", "product": {"id": 1, "name": "New Product"}}
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'stock_quantity' => 'required|integer',
            'low_stock_threshold' => 'nullable|integer|min:1',
            'price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'category_id' => 'required|integer|exists:categories,id',
            'units_id' => 'required|integer|exists:units,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
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
            'code' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'name' => 'required|string|max:255',
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
            return redirect()->route('admin.product')->with('success', 'Product updated successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Product update failed for ID ' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating product. Please try again.');
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $this->productService->deleteProduct($product);
            return redirect()->route('admin.product')->with('success', 'Product deleted');
        } catch (\Exception $e) {
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
     * @summary Bulk Delete Products
     * @bodyParam ids array required An array of product IDs to delete. Example: [1, 2, 3]
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
     * @summary Bulk Export Products
     * @bodyParam ids array required An array of product IDs to export. Example: [1, 2, 3]
     * @bodyParam export_option string required The export format ('pdf' or 'csv'). Example: "csv"
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
        ]);

        try {
            $result = $this->productService->bulkUpdateStock(
                $request->updates,
                $request->reason,
                auth()->id()
            );
            $updatedProductsWithBadges = [];
            foreach ($result['changes'] as $change) {
                // Assuming 'low_stock_threshold' is returned in the $change array from ProductService
                $lowStockThreshold = $change['low_stock_threshold'] ?? 10; // Default to 10 if not provided
                [$badgeClass, $badgeText] = \App\Helpers\ProductHelper::getStockClassAndText($change['new_stock_quantity'], $lowStockThreshold);
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
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $adjustedProduct = $this->productService->adjustProductStock(
                $product,
                $request->adjustment_amount,
                $request->adjustment_type,
                $request->reason,
                auth()->id() // Pass the authenticated user's ID
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'product' => $adjustedProduct,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adjusting stock: ' . $e->getMessage(),
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
            $adjustments = \App\Models\StockAdjustment::where('product_id', $id)
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