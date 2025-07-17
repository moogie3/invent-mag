<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Categories;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\PosService;
use Illuminate\Http\Request;

class POSController extends Controller
{
    protected $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function index()
    {
        $products = Product::with('unit')->get();
        $customers = Customer::all();
        $walkInCustomerId = $customers->where('name', 'Walk In Customer')->first()->id ?? null;
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();

        return view('admin.pos.index', compact('products', 'customers', 'walkInCustomerId', 'categories', 'units', 'suppliers'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'transaction_date' => 'required|date',
                'customer_id' => 'nullable|exists:customers,id',
                'products' => 'required|json',
                'discount_total' => 'nullable|numeric|min:0',
                'discount_total_type' => 'nullable|in:fixed,percentage',
                'tax_rate' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'payment_method' => 'required|string|in:Cash,Card,Transfer,eWallet',
                'amount_received' => 'nullable|numeric|min:0|required_if:payment_method,Cash',
                'change_amount' => 'nullable|numeric|min:0',
            ]);

            $sale = $this->posService->createSale($request->all());

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Transaction completed successfully.', 'sale_id' => $sale->id]);
            }

            return redirect()->route('admin.pos.receipt', $sale->id)->with('success', 'Transaction completed successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function receipt($id)
    {
        $sale = Sales::with(['salesItems.product', 'customer'])->findOrFail($id);
        $totalBeforeDiscount = 0;
        $totalItemDiscount = 0;

        foreach ($sale->salesItems as $item) {
            $discountAmount = \App\Helpers\SalesHelper::calculateDiscountPerUnit(
                $item->customer_price,
                $item->discount,
                $item->discount_type
            ) * $item->quantity;

            $item->calculated_total = \App\Helpers\SalesHelper::calculateTotal(
                $item->customer_price,
                $item->quantity,
                $item->discount,
                $item->discount_type
            );

            $totalBeforeDiscount += $item->customer_price * $item->quantity;
            $totalItemDiscount += $discountAmount;
        }

        $subTotal = $totalBeforeDiscount - $totalItemDiscount;
        $orderDiscount = $sale->order_discount ?? 0;
        $orderDiscountType = $sale->order_discount_type ?? 'fixed';
        $orderDiscountAmount = \App\Helpers\SalesHelper::calculateDiscount(
            $totalBeforeDiscount,
            $orderDiscount,
            $orderDiscountType
        );

        $taxableAmount = $subTotal - $orderDiscountAmount;
        $taxRate = $sale->tax_rate ?? 0;
        $taxAmount = \App\Helpers\SalesHelper::calculateTaxAmount($taxableAmount, $taxRate);
        $grandTotal = $taxableAmount + $taxAmount;
        $amountReceived = $sale->amount_received ?? $grandTotal;
        $change = $amountReceived - $grandTotal;

        return view('admin.pos.receipt', compact(
            'sale',
            'subTotal',
            'orderDiscountAmount',
            'taxRate',
            'taxAmount',
            'grandTotal',
            'amountReceived',
            'change'
        ));
    }

    public function printReceipt($id)
    {
        $sale = Sales::with(['salesItems.product', 'customer'])->findOrFail($id);
        return view('admin.pos.print', compact('sale'));
    }
}