<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class POSController extends Controller
{
    public function index()
    {
        $products = Product::with('unit')->get();
        $customers = Customer::all();
        $walkInCustomerId = $customers->where('name', 'Walk In Customer')->first()->id ?? null;

        return view('admin.pos.index', compact('products', 'customers', 'walkInCustomerId'));
    }

    public function store(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
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

            // Decode products
            $products = json_decode($request->products, true);
            if (!$products || !is_array($products)) {
                return back()
                    ->withErrors(['products' => 'Invalid product data'])
                    ->withInput();
            }

            // Calculate totals
            $subTotal = 0;
            $totalBeforeDiscounts = 0;

            foreach ($products as $product) {
                $quantity = $product['quantity'];
                $price = $product['price'];

                $productSubtotal = $price * $quantity;
                $totalBeforeDiscounts += $productSubtotal;
                $subTotal += $productSubtotal;
            }

            // Calculate order discount
            $orderDiscount = $request->discount_total ?? 0;
            $orderDiscountType = $request->discount_total_type ?? 'fixed';
            $orderDiscountAmount = $orderDiscountType === 'percentage' ? ($totalBeforeDiscounts * $orderDiscount) / 100 : $orderDiscount;

            // Calculate tax using the user-provided tax rate from the UI
            $taxable = $subTotal - $orderDiscountAmount;
            $taxRate = $request->tax_rate ?? 0; // Use the tax_rate from request
            $taxAmount = $request->tax_amount ?? $taxable * ($taxRate / 100);
            $grandTotal = $request->grand_total ?? $taxable + $taxAmount;

            // Get the authenticated user's ID and timezone
            $userId = Auth::id();
            $userTimezone = Auth::user()->timezone ?? config('app.timezone');

            $transactionDate = \Carbon\Carbon::parse($request->transaction_date, $userTimezone)->setTimezone('UTC')->format('Y-m-d');
            $transactionDate = $request->transaction_date;

            // Generate invoice number
            $lastInvoice = Sales::latest()->first();
            $invoiceNumber = $lastInvoice ? intval(substr($lastInvoice->invoice, -4)) + 1 : 1;
            $invoice = 'POS-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);

            // Get the authenticated user's ID
            $userId = Auth::id();

            // Create Sale with POS transaction - Fix the timezone handling
            $sale = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $request->customer_id,
                'user_id' => Auth::id(),
                'order_date' => $request->transaction_date, // The model will handle timezone conversion
                'due_date' => $request->transaction_date, // The model will handle timezone conversion
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'order_discount' => $orderDiscountAmount,
                'order_discount_type' => $orderDiscountType,
                'status' => 'Paid',
                'payment_type' => $request->payment_method,
                'amount_received' => $request->amount_received ?? $grandTotal,
                'change_amount' => $request->change_amount ?? 0,
                'payment_date' => now(), // The model will handle timezone conversion
                'is_pos' => true,
            ]);

            // Insert sale items
            foreach ($products as $product) {
                $productSubtotal = $product['price'] * $product['quantity'];

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'customer_price' => $product['price'],
                    'discount' => 0, // No item-level discounts in current POS UI
                    'discount_type' => 'fixed',
                    'total' => $productSubtotal,
                ]);

                // Reduce stock quantity - check if your field is named stock or stock_quantity
                $productModel = Product::find($product['id']);
                if ($productModel) {
                    // Use the correct field name based on your database structure
                    if (isset($productModel->stock_quantity)) {
                        $productModel->decrement('stock_quantity', $product['quantity']);
                    } elseif (isset($productModel->quantity)) {
                        $productModel->decrement('quantity', $product['quantity']);
                    } elseif (isset($productModel->stock)) {
                        $productModel->decrement('stock', $product['quantity']);
                    }
                }
            }

            // Prepare receipt data for redirect
            return redirect()->route('admin.pos.receipt', $sale->id)->with('success', 'Transaction completed successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function receipt($id)
    {
        $sale = Sales::with(['items.product', 'customer'])->findOrFail($id);

        // Start calculating totals
        $totalBeforeDiscount = 0;
        $totalItemDiscount = 0;

        foreach ($sale->items as $item) {
            $discountAmount = \App\Helpers\SalesHelper::calculateItemDiscountAmount($item->customer_price, $item->quantity, $item->discount, $item->discount_type);

            $itemTotal = \App\Helpers\SalesHelper::calculateItemTotal($item->customer_price, $item->quantity, $item->discount, $item->discount_type);

            $totalBeforeDiscount += $item->customer_price * $item->quantity;
            $totalItemDiscount += $discountAmount;
        }

        $subTotal = $totalBeforeDiscount - $totalItemDiscount;

        // Order Discount
        $orderDiscount = $sale->order_discount ?? 0;
        $orderDiscountType = $sale->order_discount_type ?? 'fixed';
        $orderDiscountAmount = \App\Helpers\SalesHelper::calculateOrderDiscount($totalBeforeDiscount, $orderDiscount, $orderDiscountType);

        // Tax
        $taxableAmount = $subTotal - $orderDiscountAmount;
        $taxRate = $sale->tax_rate ?? 0;
        $taxAmount = \App\Helpers\SalesHelper::calculateTaxAmount($taxableAmount, $taxRate);

        // Grand Total
        $grandTotal = $taxableAmount + $taxAmount;

        // Payment
        $amountReceived = $sale->amount_received ?? $grandTotal;
        $change = $amountReceived - $grandTotal;

        return view('admin.pos.receipt', compact('sale', 'subTotal', 'orderDiscountAmount', 'taxRate', 'taxAmount', 'grandTotal', 'amountReceived', 'change'));
    }

    public function printReceipt($id)
    {
        $sale = Sales::with(['items.product', 'customer'])->findOrFail($id);
        return view('admin.pos.print', compact('sale'));
    }
}
