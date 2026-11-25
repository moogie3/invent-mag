<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(protected ProductService $productService) {}

    /**
     * Product search (name, code, barcode, category, supplier).
     *
     * General product lookup for the mobile app — broader than the POS
     * catalog (also matches barcode/category/supplier).
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @queryParam q string required Search term.
     *
     * @response scenario=success [
     *   {"id": 1, "code": "SKU-1", "name": "Widget", "barcode": "1234567890", "selling_price": 15000, "main_warehouse_stock": 10}
     * ]
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:100',
        ]);

        try {
            $products = $this->productService->searchProducts(trim($request->get('q')));

            return response()->json(
                $products->map(fn (Product $product) => $this->transform($product))->values()
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching products.',
            ], 500);
        }
    }

    /**
     * Look up a product by barcode.
     *
     * For hardware scanner input: the scanner types the barcode into `barcode`
     * and this returns the matching product (or 404).
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @queryParam barcode string required Exact barcode value.
     *
     * @response scenario=success {
     *   "id": 1,
     *   "code": "SKU-1",
     *   "name": "Widget",
     *   "barcode": "1234567890",
     *   "selling_price": 15000,
     *   "unit_symbol": "pcs",
     *   "main_warehouse_stock": 10
     * }
     * @response status=404 scenario="not found" {
     *   "message": "Product not found"
     * }
     */
    public function searchByBarcode(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => 'required|string|max:100',
        ]);

        try {
            $product = $this->productService->searchByBarcode($request->get('barcode'));

            if (! $product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            return response()->json($this->transform($product));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching by barcode.',
            ], 500);
        }
    }

    /**
     * Product detail by id.
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @urlParam id integer required Product id.
     *
     * @response scenario=success {
     *   "id": 1,
     *   "code": "SKU-1",
     *   "name": "Widget",
     *   "barcode": "1234567890",
     *   "selling_price": 15000,
     *   "main_warehouse_stock": 10
     * }
     * @response status=404 scenario="not found" {
     *   "message": "Resource not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['unit', 'category', 'productWarehouses'])->findOrFail($id);

        return response()->json($this->transform($product));
    }

    /**
     * Slim product payload for mobile lists and cart lines.
     *
     * Stock accessors need productWarehouses loaded (or they lazy-load);
     * barcode search only eager-loads unit, so stock still resolves via
     * the model's own queries for a single row.
     */
    private function transform(Product $product): array
    {
        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'description' => $product->description,
            'selling_price' => (float) $product->selling_price,
            'price' => (float) $product->price,
            'unit_symbol' => $product->unit?->symbol,
            'category_name' => $product->category?->name,
            'image' => $product->image,
            'main_warehouse_stock' => (float) $product->main_warehouse_stock,
            'total_stock' => (float) $product->total_stock,
            'low_stock_threshold' => $product->low_stock_threshold,
        ];
    }
}
