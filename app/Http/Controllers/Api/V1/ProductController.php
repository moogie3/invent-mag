<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @group Products
 *
 * APIs for managing products
 */
class ProductController extends Controller
{
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
        return ProductResource::collection($products);
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
}
