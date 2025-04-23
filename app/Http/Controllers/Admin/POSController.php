<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;

class POSController extends Controller
{
    public function index()
    {
        $pos = Purchase::all();
        $products = Product::with('unit')->get();
        $customers = Customer::all();
        return view('admin.pos.index', compact('pos', 'customers', 'products'));
    }

    public function store(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'transaction_date' => 'required|date',
                'products' => 'required|json',
                'discount_total' => 'nullable|numeric|min:0',
                'discount_total_type' => 'nullable|in:fixed,percentage',
                'tax_rate' => 'required|numeric|min:0|max:100',
                'tax_amount' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'payment_method' => 'required|string|in:cash,card,transfer,ewallet',
                'amount_received' => 'nullable|numeric|min:0|required_if:payment_method,cash',
                'change_amount' => 'nullable|numeric|min:0',
            ]);

            // Decode products
            $products = json_decode($request->products, true);
            if (!$products || !is_array($products)) {
                return back()
                    ->withErrors(['products' => 'Invalid product data'])
                    ->withInput();
            }

            // Calculate totals (using values already calculated in frontend)
            $subTotal = array_sum(array_column($products, 'total'));
            $orderDiscount = $request->discount_total ?? 0;
            $orderDiscountType = $request->discount_total_type;
            $taxAmount = $request->tax_amount;
            $grandTotal = $request->grand_total;

            // Generate invoice number
            $lastInvoice = POS::latest()->first();
            $invoiceNumber = $lastInvoice ? intval(substr($lastInvoice->invoice, -4)) + 1 : 1;
            $invoice = 'POS-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);

            // Create POS transaction
            $pos = POS::create([
                'invoice' => $invoice,
                'customer_id' => $request->customer_id, // Nullable if guest purchase
                'transaction_date' => $request->transaction_date,
                'tax_rate' => $request->tax_rate,
                'total_tax' => $taxAmount,
                'subtotal' => $subTotal,
                'total' => $grandTotal,
                'order_discount' => $orderDiscount,
                'order_discount_type' => $orderDiscountType,
                'payment_method' => $request->payment_method,
                'amount_received' => $request->amount_received,
                'change_amount' => $request->change_amount,
                'status' => 'Completed', // Default status for POS is completed
            ]);

            // Insert POS items
            foreach ($products as $product) {
                POSItem::create([
                    'pos_id' => $pos->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'total' => $product['total'],
                ]);

                // Update inventory
                $productModel = Product::find($product['id']);
                if ($productModel) {
                    $productModel->stock = $productModel->stock - $product['quantity'];
                    $productModel->save();
                }
            }

            return redirect()->route('admin.pos')->with('success', 'POS transaction completed successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
