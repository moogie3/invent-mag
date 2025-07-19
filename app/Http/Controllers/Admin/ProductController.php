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
        $expiringSoonCount = Product::expiringSoonCount();
        $totalcategory = Categories::count();

        $formData = $this->productService->getProductFormData();

        $lowStockProducts = Product::getLowStockProducts();
        $expiringSoonProducts = Product::getExpiringSoonProducts();

        return view('admin.product.index', compact('totalcategory', 'products', 'entries', 'totalproduct', 'lowStockCount', 'expiringSoonCount', 'lowStockProducts', 'expiringSoonProducts') + $formData);
    }

    public function create()
    {
        $formData = $this->productService->getProductFormData();

        return view('admin.product.product-create', $formData);
    }

    public function modalView($id)
    {
        try {
            $product = Product::with(['category', 'supplier', 'unit', 'warehouse'])->findOrFail($id);
            $product->formatted_price = \App\Helpers\CurrencyHelper::format($product->price);
            $product->formatted_selling_price = \App\Helpers\CurrencyHelper::format($product->selling_price);
            if ($product->has_expiry && $product->expiry_date) {
                try {
                    $product->expiry_date = \Carbon\Carbon::parse($product->expiry_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    $product->expiry_date = null;
                }
            }
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
        $expiringSoonProducts = Product::getExpiringSoonProducts();

        return view('admin.product.product-edit', compact('expiringSoonProducts', 'lowStockProducts', 'products', 'categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse'));
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
            'expiry_date' => 'nullable|date|required_if:has_expiry,1',
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
        $totalcategory = \App\Models\Categories::count();
        $lowStockCount = Product::lowStockCount();
        $expiringSoonCount = Product::expiringSoonCount();

        return response()->json([
            'totalproduct' => $totalproduct,
            'totalcategory' => $totalcategory,
            'lowStockCount' => $lowStockCount,
            'expiringSoonCount' => $expiringSoonCount,
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
        ]);

        try {
            $result = $this->productService->bulkUpdateStock($request->updates);
            return response()->json([
                'success' => true,
                'message' => "Successfully updated stock for {$result['updated_count']} product(s)",
                'updated_count' => $result['updated_count'],
                'changes' => $result['changes'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating stock quantities. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));
        $products = $this->productService->searchProducts($query);

        return response()->json([
            'success' => true,
            'products' => $products,
            'count' => $products->count(),
            'query' => $query,
            'message' => $products->count() > 0 ? 'Products found' : 'No products found'
        ]);
    }
}
