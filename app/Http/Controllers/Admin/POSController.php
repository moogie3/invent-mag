<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Tax;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class POSController extends Controller
{
    protected $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function index()
    {
        $data = $this->posService->getPosIndexData();
        $data['taxes'] = Tax::active()->orderBy('name')->get();

        return view('admin.pos.index', $data);
    }

    /**
     * Paginated product search for the POS catalog grid (used by the search
     * box and the Load more button; the initial page is server-rendered).
     */
    public function searchProducts(Request $request)
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
            'products' => $result['products']->map(fn (Product $product) => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'selling_price' => (float) $product->selling_price,
                'unit_symbol' => $product->unit?->symbol,
                'main_warehouse_stock' => (int) $product->main_warehouse_stock,
                'image' => $product->image,
                'is_placeholder' => $product->image === $placeholder,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        if (! Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('admin.login');
        }

        try {
            $request->validate([
                'transaction_date' => 'required|date',
                'customer_id' => 'nullable|exists:customers,id',
                'products' => 'required|json',
                'discount_total' => 'nullable|numeric|min:0',
                'discount_total_type' => 'nullable|in:fixed,percentage',
                'grand_total' => 'required|numeric|min:0',
                'payment_method' => 'required|string|in:Cash,Card,Transfer,eWallet',
                'amount_received' => 'nullable|numeric|min:0|required_if:payment_method,Cash',
                'change_amount' => 'nullable|numeric|min:0',
                'tax_id' => 'nullable|exists:taxes,id',
                'pph_tax_id' => 'nullable|exists:taxes,id',
            ]);

            $sale = $this->posService->createSale($request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction completed successfully.',
                    'sale_id' => $sale->id,
                ]);
            }

            return redirect()->route('admin.pos.receipt', $sale->id)->with('success', 'Transaction completed successfully.');

        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function receipt($id)
    {
        $sale = Sales::with(['salesItems.product', 'customer'])->findOrFail($id);
        $receiptData = $this->posService->getReceiptData($sale);

        return view('admin.pos.receipt', $receiptData);
    }

    public function printReceipt($id)
    {
        $sale = Sales::with(['salesItems.product', 'customer'])->findOrFail($id);
        $receiptData = $this->posService->getReceiptData($sale);

        return view('admin.pos.print', $receiptData);
    }

    public function validateStock(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $insufficientProducts = $this->posService->validateStockBeforeSale($request->input('products'));

            return response()->json([
                'success' => true,
                'available' => empty($insufficientProducts),
                'items' => $insufficientProducts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
