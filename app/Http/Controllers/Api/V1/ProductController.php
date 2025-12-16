<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

/**
 * @group Products
 *
 * APIs for managing products
 */
class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
        $this->middleware('permission:view-products')->only(['index', 'show']);
        $this->middleware('permission:create-products')->only(['store', 'quickCreate']);
        $this->middleware('permission:edit-products')->only(['update', 'bulkUpdateStock', 'adjustStock']);
        $this->middleware('permission:delete-products')->only(['destroy', 'bulkDelete']);
    }

    /**
     * Display a listing of the products.
     *
     * Retrieves a paginated list of products. You can specify the number of products per page.
     *
     * @group Products
     * @authenticated
     * @queryParam per_page int The number of products to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"code":"P-001","name":"Product Name","stock_quantity":100,...}],"links":{"first":"...","last":"...","prev":null,"next":"..."},"meta":{"current_page":1,"from":1,"total":10,...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $products = Product::with(['warehouse', 'category', 'supplier', 'unit'])->paginate($perPage);

        $totalproduct = Product::count();
        $lowStockCount = Product::lowStockCount();
        $expiringSoonCount = $this->productService->getExpiringSoonPOItemsCount();
        $totalcategory = \App\Models\Categories::count();

        return ProductResource::collection($products)->additional([
            'meta' => [
                'total_products' => $totalproduct,
                'low_stock_count' => $lowStockCount,
                'expiring_soon_count' => $expiringSoonCount,
                'total_categories' => $totalcategory,
            ],
        ]);
    }

    /**
     * Create a new product.
     *
     * Creates a new product with the provided data.
     *
     * @group Products
     * @authenticated
     * @bodyParam name string required The name of the product. Example: Laptop Pro
     * @bodyParam code string required A unique code for the product. Example: LP-001
     * @bodyParam price float required The cost price of the product. Example: 1200.50
     * @bodyParam selling_price float required The selling price of the product. Example: 1499.99
     * @bodyParam category_id int required The ID of the category. Example: 2
     * @bodyParam units_id int required The ID of the unit. Example: 1
     * @bodyParam supplier_id int The ID of the supplier. Example: 1
     * @bodyParam warehouse_id int The ID of the warehouse. Example: 1
     * @bodyParam stock_quantity float The initial stock quantity. Example: 50
     * @bodyParam description string A description for the product.
     *
     * @response 201 scenario="Success" {"data":{"id":15,"code":"LP-001","name":"Laptop Pro","price":1200.5,"selling_price":1499.99,"stock_quantity":50,...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->createProduct($request->validated());
        return new ProductResource($product);
    }

    /**
     * Display the specified product.
     *
     * Retrieves a single product by its ID.
     *
     * @group Products
     * @authenticated
     * @urlParam product required The ID of the product. Example: 4
     *
     * @response 200 scenario="Success" {"data":{"id":4,"code":"TR14","name":"Low Stock LED","price":90,"selling_price":160,...}}
     * @response 404 scenario="Not Found" {"message": "Product not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Product $product)
    {
        return new ProductResource($product->load(['warehouse', 'category', 'supplier', 'unit']));
    }

    /**
     * Update the specified product.
     *
     * Updates a product with the provided data.
     *
     * @group Products
     * @authenticated
     * @urlParam product required The ID of the product to update. Example: 7
     * @bodyParam name string The name of the product. Example: Laptop Pro v2
     * @bodyParam code string The product code. Must be unique. Example: LP-001
     * @bodyParam price float The cost price of the product. Example: 1250.00
     * @bodyParam selling_price float The selling price of the product. Example: 1499.99
     * @bodyParam category_id int The ID of the category. Example: 2
     * @bodyParam units_id int The ID of the unit. Example: 1
     * @bodyParam supplier_id int The ID of the supplier. Example: 1
     * @bodyParam warehouse_id int The ID of the warehouse. Example: 1
     * @bodyParam stock_quantity float The initial stock quantity. Example: 50
     * @bodyParam description string A description for the product.
     *
     * @response 200 scenario="Success" {"data":{"id":7,"code":"LP-001","name":"Laptop Pro v2",...}}
     * @response 404 scenario="Not Found" {"message": "Product not found."}
     * @response 422 scenario="Validation Error" {"message":"The code has already been taken.","errors":{"code":["The code has already been taken."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->productService->updateProduct($product, $request->validated());
        return new ProductResource($product);
    }

    /**
     * Delete the specified product.
     *
     * Deletes a single product by its ID.
     *
     * @group Products
     * @authenticated
     * @urlParam product required The ID of the product to delete. Example: 7
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Product not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Product $product)
    {
        $this->productService->deleteProduct($product);
        return response()->noContent();
    }

    /**
     * Quick Create Product
     *
     * @group Products
     * @authenticated
     * @bodyParam code string required The product code. Example: "PROD-001"
     * @bodyParam name string required The name of the product. Example: "New Product"
     * @bodyParam stock_quantity integer required The initial stock quantity. Example: 100
     * @bodyParam price number required The purchase price. Example: 99.99
     * @bodyParam selling_price number required The selling price. Example: 149.99
     * @bodyParam category_id integer required The ID of the category. Example: 1
     * @bodyParam units_id integer required The ID of the unit. Example: 1
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam warehouse_id integer nullable The ID of the warehouse. Example: 1
     * @bodyParam description string nullable A description of the product.
     * @bodyParam image file nullable An image of the product.
     * @bodyParam has_expiry boolean nullable Whether the product has an expiry date. Example: false
     *
     * @response 200 scenario="Success" {"success":true,"message":"Product created successfully","product":{"id":16,"code":"PROD-001",...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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

        $product = $this->productService->quickCreateProduct($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => new ProductResource($product),
        ]);
    }

    /**
     * Get Product Metrics
     *
     * @group Products
     * @authenticated
     *
     * @response 200 scenario="Success" {"total_products":10,"total_categories":5,"low_stock_count":2}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getProductMetrics()
    {
        $totalproduct = Product::count();
        $totalcategory = \App\Models\Categories::count();
        $lowStockCount = Product::lowStockCount();

        return response()->json([
            'total_products' => $totalproduct,
            'total_categories' => $totalcategory,
            'low_stock_count' => $lowStockCount,
        ]);
    }

    /**
     * Bulk Delete Products
     *
     * @group Products
     * @authenticated
     * @bodyParam ids array required An array of product IDs to delete. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A product ID.
     *
     * @response 200 scenario="Success" {"success":true,"message":"Successfully deleted 3 product(s)","deleted_count":3,"images_deleted":0}
     * @response 422 scenario="Validation Error" {"message":"The ids field is required.","errors":{"ids":["The ids field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        $result = $this->productService->bulkDeleteProducts($request->ids);
        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$result['deleted_count']} product(s)",
            'deleted_count' => $result['deleted_count'],
            'images_deleted' => $result['images_deleted'],
        ]);
    }

    /**
     * Bulk Update Product Stock
     *
     * @group Products
     * @authenticated
     * @bodyParam updates.*.id integer required The ID of the product to update. Example: 4
     * @bodyParam updates.*.stock_quantity integer required The new stock quantity. Example: 100
     * @bodyParam reason string nullable A reason for the stock adjustment. Example: Annual stock take
     *
     * @response 200 scenario="Success" {"success":true,"message":"Successfully updated stock for 2 product(s)","updated_count":2,"changes":[{"id":4,"old_stock":50,"new_stock":100,...}]}
     * @response 422 scenario="Validation Error" {"message":"The updates field is required.","errors":{"updates":["The updates field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function bulkUpdateStock(Request $request)
    {
        $request->validate([
            'updates' => 'required|array|min:1',
            'updates.*.id' => 'required|integer|exists:products,id',
            'updates.*.stock_quantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->productService->bulkUpdateStock(
            $request->updates,
            $request->reason,
            auth()->id()
        );
        $updatedProductsWithBadges = [];
        foreach ($result['changes'] as $change) {
            $lowStockThreshold = $change['low_stock_threshold'] ?? 10;
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
    }

    /**
     * Adjust Product Stock
     *
     * @group Products
     * @authenticated
     * @bodyParam product_id integer required The ID of the product to adjust. Example: 4
     * @bodyParam adjustment_amount number required The amount to adjust the stock by. Example : 100
     * @bodyParam adjustment_type string required The type of adjustment. Must be one of increase, decrease, correction. Example: increase
     * @bodyParam reason string nullable A reason for the adjustment. Example: Found extra unit during count
     *
     * @response 200 scenario="Success" {"success":true,"message":"Stock adjusted successfully.","product":{"id":4,...}}
     * @response 404 scenario="Not Found" {"message": "Product not found."}
     * @response 422 scenario="Validation Error" {"message":"The adjustment_amount must be a number.","errors":{"adjustment_amount":["The adjustment_amount must be a number."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'adjustment_amount' => 'required|numeric|min:1',
            'adjustment_type' => 'required|in:increase,decrease,correction',
            'reason' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($request->product_id);
        $adjustedProduct = $this->productService->adjustProductStock(
            $product,
            $request->adjustment_amount,
            $request->adjustment_type,
            $request->reason,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted successfully.',
            'product' => new ProductResource($adjustedProduct),
        ]);
    }

    /**
     * Search Products
     *
     * @group Products
     * @authenticated
     * @queryParam q string required The search query.
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"code":"P-001","name":"Product Name",...}]}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (empty($query)) {
            $products = Product::all();
        } else {
            $products = $this->productService->searchProducts($query);
        }

        return ProductResource::collection($products);
    }

    /**
     * Get Expiring Soon Products
     *
     * @group Products
     * @authenticated
     *
     * @response 200 scenario="Success" [{"id":1,"po_id":1,"product_id":1,"quantity":10,"remaining_quantity":10,"price":100,"discount":0,"discount_type":"fixed","total":1000,"expiry_date":"2025-12-31","created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z"}]
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getExpiringSoonProducts()
    {
        $expiringSoonProducts = $this->productService->getExpiringSoonPOItems();
        return response()->json($expiringSoonProducts);
    }

    /**
     * Get Product Adjustment Log
     *
     * @group Products
     * @authenticated
     * @urlParam id integer required The ID of the product. Example: 1
     *
     * @response 200 scenario="Success" [{"id":1,"product_id":1,"adjustment_type":"increase","quantity_before":10,"quantity_after":20,"adjustment_amount":10,"reason":"Stock correction","adjusted_by":{"id":1,"name":"Admin"},"created_at":"2025-12-01T12:00:00.000000Z"}]
     * @response 404 scenario="Not Found" {"message": "Product not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getAdjustmentLog($id)
    {
        $adjustments = \App\Models\StockAdjustment::where('product_id', $id)
            ->with('adjustedBy:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($adjustments);
    }

    /**
     * Search Product by Barcode
     *
     * @group Products
     * @authenticated
     * @queryParam barcode string required The barcode to search for. Example: 7981373950433
     *
     * @response 200 scenario="Success" {"data":{"id":1,"code":"P-001","name":"Product Name",...}}
     * @response 404 scenario="Not Found" {"message": "Product not found"}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function searchByBarcode(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);
        $product = $this->productService->searchByBarcode($request->barcode);

        if ($product) {
            return new ProductResource($product);
        }

        return response()->json(['message' => 'Product not found'], 404);
    }

    /**
     * Bulk Export Products
     *
     * @group Products
     * @authenticated
     * @bodyParam ids array required An array of product IDs to export. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A product ID.
     *
     * @response 200 scenario="Success" {"success":true,"message":"Successfully exported 3 product(s)","exported_count":3,"file":"http:\/\/localhost\/storage\/exports\/products.xlsx"}
     * @response 422 scenario="Validation Error" {"message":"The ids field is required.","errors":{"ids":["The ids field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        $result = $this->productService->bulkExportProducts($request->ids);
        return response()->json([
            'success' => true,
            'message' => "Successfully exported {$result['exported_count']} product(s)",
            'exported_count' => $result['exported_count'],
            'file' => $result['file'],
        ]);
    }
}