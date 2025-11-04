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
        $entries = $request->input('entries', 10);
        $products = $this->productService->getPaginatedProducts($entries);
        $totalproduct = Product::count();
        $lowStockCount = Product::lowStockCount();
        $expiringSoonCount = $this->productService->getExpiringSoonPOItemsCount();
        $totalcategory = Categories::count();

        $formData = $this->productService->getProductFormData();

        $lowStockProducts = Product::getLowStockProducts();
        $expiringSoonProducts = $this->productService->getExpiringSoonPOItems();

        return view('admin.product.index', compact('totalcategory', 'products', 'entries', 'totalproduct', 'lowStockCount', 'lowStockProducts', 'expiringSoonCount', 'expiringSoonProducts') + $formData);
    }

    public function create()
    {
        $formData = $this->productService->getProductFormData();

        return view('admin.product.product-create', $formData);
    }

    public function modalView($id)
    {
        try {
            $product = Product::with(['category', 'supplier', 'unit', 'warehouse', 'poItems' => function($query) {
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
        ]);

        $this->productService->createProduct($request->all());

        return redirect()->route('admin.product')->with('success', 'Product created');
    }

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
        ]);

        $product = $this->productService->quickCreateProduct($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'code' => 'string',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
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
        ]);

        $this->productService->updateProduct($product, $request->all());

        return redirect()->route('admin.product')->with('success', 'Product updated successfully');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $this->productService->deleteProduct($product);

        return redirect()->route('admin.product')->with('success', 'Product deleted');
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
        $query = trim($request->get('q', ''));

        if (empty($query)) {
            // If the query is empty, return all products
            $products = Product::all();
        } else {
            // Otherwise, perform the search
            $products = $this->productService->searchProducts($query);
        }

        return response()->json($products);
    }

    public function getExpiringSoonProducts()
    {
        $expiringSoonProducts = $this->productService->getExpiringSoonPOItems();
        return response()->json($expiringSoonProducts);
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
        $request->validate(['barcode' => 'required|string']);
        $product = $this->productService->searchByBarcode($request->barcode);

        if ($product) {
            return response()->json($product);
        }

        return response()->json(['message' => 'Product not found'], 404);
    }
}