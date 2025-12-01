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
     * @group Products
     * @authenticated
     * @queryParam per_page int The number of products to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of products.
     * @responseField data[].id integer The ID of the product.
     * @responseField data[].code string The product code.
     * @responseField data[].barcode string The product barcode.
     * @responseField data[].name string The name of the product.
     * @responseField data[].stock_quantity integer The current stock quantity.
     * @responseField data[].low_stock_threshold integer The low stock threshold.
     * @responseField data[].price number The price of the product.
     * @responseField data[].selling_price number The selling price of the product.
     * @responseField data[].category_id integer The ID of the category.
     * @responseField data[].units_id integer The ID of the unit.
     * @responseField data[].supplier_id integer The ID of the supplier.
     * @responseField data[].warehouse_id integer The ID of the warehouse.
     * @responseField data[].description string The description of the product.
     * @responseField data[].image string The URL of the product image.
     * @responseField data[].has_expiry boolean Whether the product has an expiry date.
     * @responseField data[].created_at string The date and time the product was created.
     * @responseField data[].updated_at string The date and time the product was last updated.
     * @responseField meta object Additional metadata.
     * @responseField meta.total_products integer Total number of products.
     * @responseField meta.low_stock_count integer Number of products with low stock.
     * @responseField meta.expiring_soon_count integer Number of products expiring soon.
     * @responseField meta.total_categories integer Total number of categories.
     * @responseField meta.current_page integer The current page number.
     * @responseField meta.from integer The starting number of the results on the current page.
     * @responseField meta.last_page integer The last page number.
     * @responseField meta.path string The URL path.
     * @responseField meta.per_page integer The number of results per page.
     * @responseField meta.to integer The ending number of the results on the current page.
     * @responseField meta.total integer The total number of results.
     * @responseField links object Links for pagination.
     * @responseField links.first string The URL of the first page.
     * @responseField links.last string The URL of the last page.
     * @responseField links.prev string The URL of the previous page.
     * @responseField links.next string The URL of the next page.
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
     * @responseField id integer The ID of the product.
     * @responseField code string The product code.
     * @responseField barcode string The product barcode.
     * @responseField name string The name of the product.
     * @responseField stock_quantity integer The current stock quantity.
     * @responseField low_stock_threshold integer The low stock threshold.
     * @responseField price number The price of the product.
     * @responseField selling_price number The selling price of the product.
     * @responseField category_id integer The ID of the category.
     * @responseField units_id integer The ID of the unit.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField warehouse_id integer The ID of the warehouse.
     * @responseField description string The description of the product.
     * @responseField image string The URL of the product image.
     * @responseField has_expiry boolean Whether the product has an expiry date.
     * @responseField created_at string The date and time the product was created.
     * @responseField updated_at string The date and time the product was last updated.
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
     * @responseField id integer The ID of the product.
     * @responseField code string The product code.
     * @responseField barcode string The product barcode.
     * @responseField name string The name of the product.
     * @responseField stock_quantity integer The current stock quantity.
     * @responseField low_stock_threshold integer The low stock threshold.
     * @responseField price number The price of the product.
     * @responseField selling_price number The selling price of the product.
     * @responseField category_id integer The ID of the category.
     * @responseField units_id integer The ID of the unit.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField warehouse_id integer The ID of the warehouse.
     * @responseField description string The description of the product.
     * @responseField image string The URL of the product image.
     * @responseField has_expiry boolean Whether the product has an expiry date.
     * @responseField created_at string The date and time the product was created.
     * @responseField updated_at string The date and time the product was last updated.
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
     * @responseField id integer The ID of the product.
     * @responseField code string The product code.
     * @responseField barcode string The product barcode.
     * @responseField name string The name of the product.
     * @responseField stock_quantity integer The current stock quantity.
     * @responseField low_stock_threshold integer The low stock threshold.
     * @responseField price number The price of the product.
     * @responseField selling_price number The selling price of the product.
     * @responseField category_id integer The ID of the category.
     * @responseField units_id integer The ID of the unit.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField warehouse_id integer The ID of the warehouse.
     * @responseField description string The description of the product.
     * @responseField image string The URL of the product image.
     * @responseField has_expiry boolean Whether the product has an expiry date.
     * @responseField created_at string The date and time the product was created.
     * @responseField updated_at string The date and time the product was last updated.
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
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField product object The created product.
     * @responseField product.id integer The ID of the product.
     * @responseField product.code string The product code.
     * @responseField product.barcode string The product barcode.
     * @responseField product.name string The name of the product.
     * @responseField product.stock_quantity integer The current stock quantity.
     * @responseField product.low_stock_threshold integer The low stock threshold.
     * @responseField product.price number The price of the product.
     * @responseField product.selling_price number The selling price of the product.
     * @responseField product.category_id integer The ID of the category.
     * @responseField product.units_id integer The ID of the unit.
     * @responseField product.supplier_id integer The ID of the supplier.
     * @responseField product.warehouse_id integer The ID of the warehouse.
     * @responseField product.description string The description of the product.
     * @responseField product.image string The URL of the product image.
     * @responseField product.has_expiry boolean Whether the product has an expiry date.
     * @responseField product.created_at string The date and time the product was created.
     * @responseField product.updated_at string The date and time the product was last updated.
     * @response 500 scenario="Creation Failed" {"success": false, "message": "Error creating product. Please try again."}
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
     * Get Product Metrics
     *
     * @group Products
     * @authenticated
     *
     * @responseField total_products integer Total number of products.
     * @responseField total_categories integer Total number of categories.
     * @responseField low_stock_count integer Number of products with low stock.
     * @response 500 scenario="Error" {"message": "Caught exception in getProductMetrics", "error": "<error message>"}
     */
    public function getProductMetrics()
    {
        try {
            $totalproduct = Product::count();
            $totalcategory = \App\Models\Categories::count();
            $lowStockCount = Product::lowStockCount();

            return response()->json([
                'total_products' => $totalproduct,
                'total_categories' => $totalcategory,
                'low_stock_count' => $lowStockCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Caught exception in getProductMetrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk Delete Products
     *
     * @group Products
     * @authenticated
     * @bodyParam ids array required An array of product IDs to delete. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A product ID.
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField deleted_count integer The number of products successfully deleted.
     * @responseField images_deleted integer The number of images deleted.
     * @response 500 scenario="Deletion Failed" {"success": false, "message": "Error deleting products. Please try again."}
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
     * Bulk Update Product Stock
     *
     * @group Products
     * @authenticated
     * @bodyParam updates.*.id integer required The ID of the product to update. Example: 4
     * @bodyParam updates.*.stock_quantity integer required The new stock quantity. Example: 100
     * @bodyParam reason string nullable A reason for the stock adjustment. Example: Annual stock take
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField updated_count integer The number of products whose stock was updated.
     * @responseField changes array A list of changes made.
     * @response 500 scenario="Update Failed" {"success": false, "message": "Error updating stock quantities. Please try again."}
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
     * Adjust Product Stock
     *
     * @group Products
     * @authenticated
     * @bodyParam product_id integer required The ID of the product to adjust. Example: 4
     * @bodyParam adjustment_amount number required The amount to adjust the stock by. Example : 100
     * @bodyParam adjustment_type string required The type of adjustment. Must be one of increase, decrease, correction. Example: increase
     * @bodyParam reason string nullable A reason for the adjustment. Example: Found extra unit during count
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField product object The adjusted product.
     * @responseField product.id integer The ID of the product.
     * @responseField product.code string The product code.
     * @responseField product.barcode string The product barcode.
     * @responseField product.name string The name of the product.
     * @responseField product.stock_quantity integer The current stock quantity.
     * @responseField product.low_stock_threshold integer The low stock threshold.
     * @responseField product.price number The price of the product.
     * @responseField product.selling_price number The selling price of the product.
     * @responseField product.category_id integer The ID of the category.
     * @responseField product.units_id integer The ID of the unit.
     * @responseField product.supplier_id integer The ID of the supplier.
     * @responseField product.warehouse_id integer The ID of the warehouse.
     * @responseField product.description string The description of the product.
     * @responseField product.image string The URL of the product image.
     * @responseField product.has_expiry boolean Whether the product has an expiry date.
     * @responseField product.created_at string The date and time the product was created.
     * @responseField product.updated_at string The date and time the product was last updated.
     * @response 500 scenario="Adjustment Failed" {"success": false, "message": "Error adjusting stock: <error message>"}
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
     * Search Products
     *
     * @group Products
     * @authenticated
     * @queryParam q string required The search query.
     *
     * @responseField data object[] A list of products.
     * @responseField data[].id integer The ID of the product.
     * @responseField data[].code string The product code.
     * @responseField data[].barcode string The product barcode.
     * @responseField data[].name string The name of the product.
     * @responseField data[].stock_quantity integer The current stock quantity.
     * @responseField data[].low_stock_threshold integer The low stock threshold.
     * @responseField data[].price number The price of the product.
     * @responseField data[].selling_price number The selling price of the product.
     * @responseField data[].category_id integer The ID of the category.
     * @responseField data[].units_id integer The ID of the unit.
     * @responseField data[].supplier_id integer The ID of the supplier.
     * @responseField data[].warehouse_id integer The ID of the warehouse.
     * @responseField data[].description string The description of the product.
     * @responseField data[].image string The URL of the product image.
     * @responseField data[].has_expiry boolean Whether the product has an expiry date.
     * @responseField data[].created_at string The date and time the product was created.
     * @responseField data[].updated_at string The date and time the product was last updated.
     * @response 500 scenario="Search Failed" {"success": false, "message": "Error searching products."}
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
     * Get Expiring Soon Products
     *
     * @group Products
     * @authenticated
     *
     * @responseField id integer The ID of the purchase order item.
     * @responseField po_id integer The ID of the purchase order.
     * @responseField product_id integer The ID of the product.
     * @responseField quantity integer The quantity of the product in the PO.
     * @responseField remaining_quantity integer The remaining quantity of the product.
     * @responseField price number The price of the product at purchase.
     * @responseField discount number The discount applied to the item.
     * @responseField discount_type string The type of discount applied.
     * @responseField total number The total value of the PO item.
     * @responseField expiry_date string The expiry date of the product.
     * @responseField created_at string The date and time the PO item was created.
     * @responseField updated_at string The date and time the PO item was last updated.
     * @response 200 scenario="Success" [{"id":1,"po_id":1,"product_id":1,"quantity":10,"remaining_quantity":10,"price":100,"discount":0,"discount_type":"fixed","total":1000,"expiry_date":"2025-12-31","created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z"}]
     * @response 500 scenario="Error" {"success": false, "message": "Error fetching expiring products."}
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
     * Get Product Adjustment Log
     *
     * @group Products
     * @authenticated
     * @urlParam id integer required The ID of the product. Example: 1
     *
     * @responseField id integer The ID of the stock adjustment.
     * @responseField product_id integer The ID of the product.
     * @responseField adjustment_type string The type of adjustment (increase, decrease, correction).
     * @responseField quantity_before integer The stock quantity before adjustment.
     * @responseField quantity_after integer The stock quantity after adjustment.
     * @responseField adjustment_amount integer The amount of stock adjusted.
     * @responseField reason string The reason for the adjustment.
     * @responseField adjusted_by integer The ID of the user who made the adjustment.
     * @responseField created_at string The date and time the adjustment was created.
     * @responseField updated_at string The date and time the adjustment was last updated.
     * @responseField adjusted_by object The user who made the adjustment.
     * @responseField adjusted_by.id integer The ID of the user.
     * @responseField adjusted_by.name string The name of the user.
     * @response 200 scenario="Success" [{"id":1,"product_id":1,"adjustment_type":"increase","quantity_before":10,"quantity_after":20,"adjustment_amount":10,"reason":"Stock correction","adjusted_by":1,"created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z","adjusted_by":{"id":1,"name":"Admin"}}]
     * @response 500 scenario="Error" {"success": false, "message": "Error fetching adjustment log."}
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
     * Search Product by Barcode
     *
     * @group Products
     * @authenticated
     * @queryParam barcode string required The barcode to search for. Example: 7981373950433
     * @responseField id integer The ID of the product.
     * @responseField code string The product code.
     * @responseField barcode string The product barcode.
     * @responseField name string The name of the product.
     * @responseField stock_quantity integer The current stock quantity.
     * @responseField low_stock_threshold integer The low stock threshold.
     * @responseField price number The price of the product.
     * @responseField selling_price number The selling price of the product.
     * @responseField category_id integer The ID of the category.
     * @responseField units_id integer The ID of the unit.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField warehouse_id integer The ID of the warehouse.
     * @responseField description string The description of the product.
     * @responseField image string The URL of the product image.
     * @responseField has_expiry boolean Whether the product has an expiry date.
     * @responseField created_at string The date and time the product was created.
     * @responseField updated_at string The date and time the product was last updated.
     * @response 404 scenario="Not Found" {"message": "Product not found"}
     * @response 500 scenario="Error" {"success": false, "message": "Error searching by barcode."}
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
     * Bulk Export Products
     *
     * @group Products
     * @authenticated
     * @bodyParam ids array required An array of product IDs to export. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A product ID.
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField exported_count integer The number of products successfully exported.
     * @responseField file string The URL to the exported file.
     * @response 422 {
     *  "message": "The ids field is required.",
     *  "errors": {
     *    "ids": ["The ids field is required."]
     *  }
     * }
     * @response 500 scenario="Export Failed" {"success": false, "message": "Error exporting products. Please try again."}
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
