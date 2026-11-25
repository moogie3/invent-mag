<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\Tax;
use App\Rules\TaxIsSelectable;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function __construct(protected PosService $posService) {}

    /**
     * Paginated POS catalog search.
     *
     * Same catalog the web POS grid uses (name/code match, main-warehouse
     * stock on each row).
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @queryParam q string Optional search term (name or code).
     * @queryParam offset integer Pagination offset (default 0).
     *
     * @response scenario=success {
     *   "success": true,
     *   "has_more": false,
     *   "products": [{"id": 1, "code": "SKU-1", "name": "Widget", "selling_price": 15000, "unit_symbol": "pcs", "main_warehouse_stock": 10}]
     * }
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $result = $this->posService->searchProducts(
            $validated['q'] ?? '',
            (int) ($validated['offset'] ?? 0),
            PosService::CATALOG_PAGE_SIZE,
        );

        $placeholder = asset('img/default_placeholder.png');

        return response()->json([
            'success' => true,
            'has_more' => $result['has_more'],
            'products' => $result['products']->map(fn ($product) => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'selling_price' => (float) $product->selling_price,
                'unit_symbol' => $product->unit?->symbol,
                'main_warehouse_stock' => (int) $product->main_warehouse_stock,
                'total_stock' => (int) $product->total_stock,
                'image' => $product->image,
                'is_placeholder' => $product->image === $placeholder,
            ])->values(),
        ]);
    }

    /**
     * Create a POS sale.
     *
     * Accepts the same payload shape as the web POS, except `products` may
     * be a JSON array (preferred for mobile) instead of a JSON string.
     * Business logic is entirely in PosService — identical totals, tax,
     * stock deduction, and journal posting as the web terminal.
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @bodyParam transaction_date string required Sale date (Y-m-d).
     * @bodyParam customer_id integer Optional customer id.
     * @bodyParam products array required Line items: [{id, quantity, price, total?}].
     * @bodyParam discount_total number Optional order discount amount.
     * @bodyParam discount_total_type string Optional fixed|percentage.
     * @bodyParam grand_total number required Client-computed grand total.
     * @bodyParam payment_method string required Cash|Card|Transfer|eWallet.
     * @bodyParam amount_received number Required when payment_method is Cash.
     * @bodyParam change_amount number Optional change given.
     * @bodyParam tax_id integer Optional selectable tax id.
     * @bodyParam pph_tax_id integer Optional withholding tax id.
     *
     * @response scenario=success {
     *   "success": true,
     *   "message": "Transaction completed successfully.",
     *   "sale_id": 42
     * }
     * @response status=422 scenario=validation {
     *   "message": "The given data was invalid.",
     *   "errors": {"products": ["The products field is required."]}
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'transaction_date' => 'required|date',
                'customer_id' => 'nullable|exists:customers,id',
                'products' => 'required|array|min:1',
                'products.*.id' => 'required|integer|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
                'discount_total' => 'nullable|numeric|min:0',
                'discount_total_type' => 'nullable|in:fixed,percentage',
                'grand_total' => 'required|numeric|min:0',
                'payment_method' => 'required|string|in:Cash,Card,Transfer,eWallet',
                'amount_received' => 'nullable|numeric|min:0|required_if:payment_method,Cash',
                'change_amount' => 'nullable|numeric|min:0',
                'tax_id' => ['nullable', new TaxIsSelectable],
                'pph_tax_id' => ['nullable', new TaxIsSelectable(enforceJurisdiction: false)],
            ]);

            // PosService expects a JSON string (same as the web form).
            $validated['products'] = json_encode($validated['products']);

            $sale = $this->posService->createSale($validated);

            return response()->json([
                'success' => true,
                'message' => 'Transaction completed successfully.',
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice,
                'grand_total' => (float) $sale->total,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate stock before completing a POS sale.
     *
     * Returns which line items lack enough main-warehouse stock. Empty
     * `items` means the cart can be submitted.
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @bodyParam products array required [{id, quantity}] for each cart line.
     *
     * @response scenario=success {
     *   "success": true,
     *   "available": true,
     *   "items": []
     * }
     * @response scenario=insufficient {
     *   "success": true,
     *   "available": false,
     *   "items": [{"id": 1, "name": "Widget", "requested": 5, "available": 2, "shortage": 3}]
     * }
     */
    public function validateStock(Request $request): JsonResponse
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $insufficient = $this->posService->validateStockBeforeSale($request->input('products'));

            return response()->json([
                'success' => true,
                'available' => empty($insufficient),
                'items' => $insufficient,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Receipt data for a completed POS sale.
     *
     * Returns the sale plus computed subtotals, tax, and change — same
     * figures as the web receipt page, as JSON.
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @urlParam id integer required Sale id.
     *
     * @response scenario=success {
     *   "sale": {"id": 42, "invoice": "POS-0042", "total": 150000},
     *   "sub_total": 150000,
     *   "order_discount_amount": 0,
     *   "tax_rate": 0,
     *   "tax_amount": 0,
     *   "pph_amount": 0,
     *   "grand_total": 150000,
     *   "amount_received": 150000,
     *   "change": 0
     * }
     */
    public function receipt(int $id): JsonResponse
    {
        $sale = Sales::with(['salesItems.product', 'customer'])->findOrFail($id);
        $data = $this->posService->getReceiptData($sale);

        return response()->json([
            'sale' => $data['sale'],
            'sub_total' => $data['subTotal'],
            'order_discount_amount' => $data['orderDiscountAmount'],
            'tax_rate' => $data['taxRate'],
            'tax_type' => $data['taxType'],
            'tax_amount' => $data['taxAmount'],
            'pph_amount' => $data['pphAmount'],
            'grand_total' => $data['grandTotal'],
            'amount_received' => $data['amountReceived'],
            'change' => $data['change'],
        ]);
    }

    /**
     * Active taxes available for the POS cart dropdown.
     *
     * @group Mobile
     * Authenticated mobile endpoints (Sanctum bearer token).
     *
     * @response scenario=success {
     *   "taxes": [{"id": 1, "name": "PPN 11%", "rate": 11, "type": "exclusive"}],
     *   "default_tax_id": 1
     * }
     */
    public function taxes(): JsonResponse
    {
        $taxes = Tax::active()->orderBy('name')->get([
            'id', 'name', 'rate', 'type', 'is_pph',
        ]);
        $default = Tax::effectiveDefault();

        return response()->json([
            'taxes' => $taxes,
            'default_tax_id' => $default?->id,
        ]);
    }
}
