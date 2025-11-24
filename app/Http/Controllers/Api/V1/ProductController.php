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
    }

    /**
     * Display a listing of the products.
     *
     * Retrieves a paginated list of products. You can specify the number of products per page.
     *
     * @queryParam per_page int The number of products to return per page. Defaults to 15. Example: 25
     *
     * @apiResourceCollection App\Http\Resources\ProductResource
     * @apiResourceModel App\Models\Product
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
     * @bodyParam name string required The name of the product. Example: "Laptop Pro"
     * @bodyParam code string required A unique code for the product. Example: "LP-001"
     * @bodyParam price float required The cost price of the product. Example: 1200.50
     * @bodyParam selling_price float required The selling price of the product. Example: 1499.99
     * @bodyParam category_id int required The ID of the category. Example: 1
     * @bodyParam units_id int required The ID of the unit. Example: 1
     * @bodyParam supplier_id int The ID of the supplier. Example: 1
     * @bodyParam warehouse_id int The ID of the warehouse. Example: 1
     * @bodyParam stock_quantity float The initial stock quantity. Example: 50
     * @bodyParam description string A description for the product.
     *
     * @apiResource App\Http\Resources\ProductResource
     * @apiResourceModel App\Models\Product
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        return new ProductResource($product);
    }

    /**
     * Display the specified product.
     *
     * Retrieves a single product by its ID.
     *
     * @urlParam product required The ID of the product. Example: 1
     *
     * @apiResource App\Http\Resources\ProductResource
     * @apiResourceModel App\Models\Product
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
     * @urlParam product required The ID of the product to update. Example: 1
     * @bodyParam name string The name of the product. Example: "Laptop Pro v2"
     * @bodyParam price float The cost price of the product. Example: 1250.00
     *
     * @apiResource App\Http\Resources\ProductResource
     * @apiResourceModel App\Models\Product
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return new ProductResource($product);
    }

    /**
     * Delete the specified product.
     *
     * Deletes a single product by its ID.
     *
     * @urlParam product required The ID of the product to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }

    /**
     * @group Products
     * @title Quick Create Product
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
     * @response {
     *  "success": true,
     *  "message": "Product created successfully",
     *  "product": {}
     * }
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
                'product' => new ProductResource($product),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @group Products
     * @title Get Product Metrics
     * @response {
     *  "totalproduct": 150,
     *  "totalcategory": 25,
     *  "lowStockCount": 10
     * }
     */
    public function getProductMetrics()
    {
        $totalproduct = Product::count();
        $totalcategory = \App\Models\Categories::count();
        $lowStockCount = Product::lowStockCount();

        return response()->json([
            'totalproduct' => $totalproduct,
            'totalcategory' => $totalcategory,
            'lowStockCount' => $lowStockCount,
        ]);
    }

    /**
     * @group Products
     * @title Bulk Delete Products
     * @bodyParam ids array required An array of product IDs to delete. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A product ID.
     *
     * @response {
     *  "success": true,
     *  "message": "Successfully deleted 3 product(s)",
     *  "deleted_count": 3,
     *  "images_deleted": 1
     * }
     * @response 422 {
     *  "message": "The ids field is required.",
     *  "errors": {
     *    "ids": ["The ids field is required."]
     *  }
     * }
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
     * @title Bulk Update Product Stock
     * @bodyParam updates array required An array of product stock updates.
     * @bodyParam updates.*.id integer required The ID of the product to update. Example: 1
     * @bodyParam updates.*.stock_quantity integer required The new stock quantity. Example: 100
     * @bodyParam reason string nullable A reason for the stock adjustment. Example: "Annual stock take"
     *
     * @response {
     *  "success": true,
     *  "message": "Successfully updated stock for 2 product(s)",
     *  "updated_count": 2,
     *  "changes": []
     * }
     */
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating stock quantities. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @group Products
     * @title Adjust Product Stock
     * @bodyParam product_id integer required The ID of the product to adjust. Example: 1
     * @bodyParam adjustment_amount number required The amount to adjust the stock by.
     * @bodyParam adjustment_type string required The type of adjustment. Must be one of 'increase', 'decrease', 'correction'. Example: 'increase'
     * @bodyParam reason string nullable A reason for the adjustment. Example: "Found extra unit during count."
     *
     * @response {
     *  "success": true,
     *  "message": "Stock adjusted successfully.",
     *  "product": {}
     * }
     */
    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'adjustment_amount' => 'required|numeric|min:1',
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
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'product' => new ProductResource($adjustedProduct),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adjusting stock: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @group Products
     * @title Search Products
     * @queryParam q string required The search query.
     *
     * @response [
     *   {
     *     "id": 1,
     *     "name": "Searched Product",
     *     "code": "SP-01"
     *   }
     * ]
     */
    public function search(Request $request)
    {
        try {
            $query = trim($request->get('q', ''));

            if (empty($query)) {
                $products = Product::all();
            } else {
                $products = $this->productService->searchProducts($query);
            }

            return ProductResource::collection($products);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching products.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @group Products
     * @title Get Expiring Soon Products
     * @response [
     *  {
     *      "id": 1,
     *      "product_name": "Product A",
     *      "expiry_date": "2025-12-31",
     *      "remaining_quantity": 10
     *  }
     * ]
     */
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

    /**
     * @group Products
     * @title Get Product Adjustment Log
     * @urlParam id integer required The ID of the product. Example: 1
     *
     * @response [
     *  {
     *      "id": 1,
     *      "product_id": 1,
     *      "adjustment_type": "increase",
     *      "quantity_change": 10,
     *      "reason": "Initial stock.",
     *      "created_at": "2025-11-23T12:00:00.000000Z"
     *  }
     * ]
     */
    public function getAdjustmentLog($id)
    {
        try {
            $adjustments = \App\Models\StockAdjustment::where('product_id', $id)
                ->with('adjustedBy:id,name')
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

    /**
     * @group Products
     * @title Search Product by Barcode
     * @queryParam barcode string required The barcode to search for.
     *
     * @response {
     *  "id": 1,
     *  "name": "Product From Barcode",
     *  "barcode": "1234567890123"
     * }
     * @response 404 {
     *  "message": "Product not found"
     * }
     */
    public function searchByBarcode(Request $request)
    {
        try {
            $request->validate(['barcode' => 'required|string']);
            $product = $this->productService->searchByBarcode($request->barcode);

            if ($product) {
                return new ProductResource($product);
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

    /**
     * @group Products
     * @title Bulk Export Products
     * @bodyParam ids array required An array of product IDs to export. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A product ID.
     *
     * @response 200 {
     *  "success": true,
     *  "message": "Successfully exported 3 product(s)"
     * }
     * @response 422 {
     *  "message": "The ids field is required.",
     *  "errors": {
     *    "ids": ["The ids field is required."]
     *  }
     * }
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        try {
            $result = $this->productService->bulkExportProducts($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully exported {$result['exported_count']} product(s)",
                'exported_count' => $result['exported_count'],
                'file' => $result['file'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting products. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
