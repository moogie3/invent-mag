<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10); // pagination
        $query = Sales::with(['product', 'customer', 'user']); // apply relationships first

        // apply filters
        if ($request->has('month') && $request->month) {
            $query->whereMonth('order_date', $request->month);
        }
        if ($request->has('year') && $request->year) {
            $query->whereYear('order_date', $request->year);
        }

        $sales = $query->paginate($entries); // apply pagination on the filtered query
        $totalinvoice = $query->count(); // count the filtered records
        $unpaidDebt = Sales::all()->where('status', 'Unpaid')->sum('total');
        $totalMonthly = Sales::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');

        // Count pending orders - defining "Pending" status
        $pendingOrders = Sales::where('status', 'Unpaid')->count();

        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.sales.index', compact('entries', 'sales', 'totalinvoice', 'shopname', 'address', 'unpaidDebt', 'pendingOrders','totalMonthly'));
    }

    public function create()
    {
        $sales = Sales::all();
        $customers = Customer::all();
        $products = Product::all();
        $items = SalesItem::all();
        $tax = Tax::first();
        $tax = Tax::where('is_active', 1)->first();
        return view('admin.sales.sales-create', compact('sales', 'customers', 'products', 'items', 'tax'));
    }

    public function edit($id)
    {
        $sales = Sales::with(['items', 'customer'])->find($id);
        $customers = Customer::all();
        $items = SalesItem::all();
        $tax = Tax::where('is_active', 1)->first();
        $isPaid = $sales->status == 'Paid';
        return view('admin.sales.sales-edit', compact('sales', 'customers', 'items', 'tax','isPaid'));
    }

    public function view($id)
    {
        $sales = Sales::with(['items', 'customer'])->find($id);

        // Check if this is a POS invoice
        if (strpos($sales->invoice, 'POS-') === 0) {
            // If it's a POS invoice, redirect to the receipt view
            return redirect()->route('admin.pos.receipt', $id);
        }

        // For regular invoices, continue with the existing code
        $customer = Customer::all();
        $items = SalesItem::all();
        $tax = Tax::first();
        return view('admin.sales.sales-view', compact('sales', 'customer', 'items', 'tax'));
    }

    public function modalView($id)
    {
        $sales = Sales::with(['customer', 'items.product'])->findOrFail($id);

        return view('admin.layouts.modals.salesmodals-view', compact('sales'));
    }

    public function store(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'invoice' => 'nullable|string|unique:sales,invoice',
                'customer_id' => 'required|exists:customers,id',
                'order_date' => 'required|date',
                'due_date' => 'required|date',
                'products' => 'required|json',
                'discount_total' => 'nullable|numeric|min:0',
                'discount_total_type' => 'nullable|in:fixed,percentage',
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
            $itemDiscountTotal = 0;
            $totalBeforeDiscounts = 0;

            foreach ($products as $product) {
                $quantity = $product['quantity'];
                $price = $product['price'];
                $discount = $product['discount'] ?? 0;
                $discountType = $product['discountType'] ?? 'fixed';

                $productSubtotal = $price * $quantity;
                $totalBeforeDiscounts += $productSubtotal;

                $discountAmount = $discountType === 'percentage' ? (($price * $discount) / 100) * $quantity : $discount * $quantity;

                $itemDiscountTotal += $discountAmount;
                $subTotal += $productSubtotal - $discountAmount;
            }

            // Calculate order discount based on totalBeforeDiscounts to match frontend
            $orderDiscount = $request->discount_total ?? 0;
            $orderDiscountType = $request->discount_total_type ?? 'fixed';
            $orderDiscountAmount = $orderDiscountType === 'percentage' ? ($totalBeforeDiscounts * $orderDiscount) / 100 : $orderDiscount;

            // Calculate tax on amount after both discounts are applied
            $taxable = $subTotal - $orderDiscountAmount;
            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;
            $taxAmount = $taxable * ($taxRate / 100);
            $grandTotal = $taxable + $taxAmount;

            // Generate invoice number if not provided
            if (empty($request->invoice)) {
                $lastInvoice = Sales::latest()->first();
                $invoiceNumber = $lastInvoice ? intval(substr($lastInvoice->invoice, -4)) + 1 : 1;
                $invoice = 'INV-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
            } else {
                $invoice = $request->invoice;
            }

            // Create Sale
            $sale = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $request->customer_id,
                'order_date' => $request->order_date,
                'due_date' => $request->due_date,
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'order_discount' => $orderDiscountAmount,
                'order_discount_type' => $orderDiscountType,
                'status' => 'Unpaid',
                'is_pos' => 0,
            ]);

            // Insert sale items
            foreach ($products as $product) {
                $productSubtotal = $product['price'] * $product['quantity'];
                $discountAmount = $product['discountType'] === 'percentage' ? ($productSubtotal * $product['discount']) / 100 : $product['discount'];

                $itemTotal = $productSubtotal - $discountAmount;

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'customer_price' => $product['price'], // Use customer_price column instead of price
                    'discount' => $product['discount'] ?? 0,
                    'discount_type' => $product['discountType'] ?? 'fixed',
                    'total' => $itemTotal,
                ]);

                $productModel = Product::find($product['id']);
                if ($productModel) {
                    if (isset($productModel->stock_quantity)) {
                        $productModel->decrement('stock_quantity', $product['quantity']);
                    } elseif (isset($productModel->quantity)) {
                        $productModel->decrement('quantity', $product['quantity']);
                    } elseif (isset($productModel->stock)) {
                        $productModel->decrement('stock', $product['quantity']);
                    }
                }
            }

            return redirect()->route('admin.sales')->with('success', 'Sale created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sales = Sales::findOrFail($id);

            // Basic sales fields update
            $sales->payment_type = $request->payment_type;
            $sales->status = $request->status;
            $sales->payment_date = $request->status === 'Paid' ? now() : null;
            $sales->order_discount_type = $request->order_discount_type_type ?? 'fixed'; // Fixed field name to match form
            $sales->order_discount = $request->order_discount ?? 0; // Fixed field name to match form

            $subTotal = 0;
            $itemDiscountTotal = 0;

            // Process items directly from request
            if (isset($request->items) && is_array($request->items)) {
                foreach ($request->items as $itemId => $itemData) {
                    $salesItem = SalesItem::findOrFail($itemId);

                    $quantity = (int) $itemData['quantity'];
                    $price = (float) $itemData['price'];
                    $discount = (float) $itemData['discount'];
                    $discountType = $itemData['discount_type'] ?? 'fixed';

                    // Calculate discount amount
                    $discountAmount = $discountType === 'percentage' ? (($price * $discount) / 100) * $quantity : $discount * $quantity;

                    $productSubtotal = $price * $quantity;
                    $itemTotal = $productSubtotal - $discountAmount;

                    $itemDiscountTotal += $discountAmount;
                    $subTotal += $itemTotal;

                    // Update the sales item
                    $salesItem->update([
                        'quantity' => $quantity,
                        'customer_price' => $price,
                        'discount' => $discount,
                        'discount_type' => $discountType,
                        'total' => $itemTotal,
                    ]);
                }
            }

            // Calculate order discount
            $orderDiscountAmount = $sales->order_discount_type === 'percentage' ? ($subTotal * $sales->order_discount) / 100 : $sales->order_discount;

            // Calculate tax using the EXISTING tax rate from the sales record
            $taxable = $subTotal - $orderDiscountAmount;
            $taxRate = $sales->tax_rate; // Use the stored tax rate instead of fetching a new one
            $taxAmount = $taxable * ($taxRate / 100);

            // Set final values - don't update the tax_rate field as we're keeping the historical rate
            $sales->total_tax = $taxAmount;
            $sales->total = $taxable + $taxAmount;
            $sales->save();

            return redirect()->route('admin.sales.view', $id)->with('success', 'Sale updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function getCustomerPrice(Customer $customer, Product $product)
    {
        // Find the most recent sale for this customer and product
        $latestSale = Sales::where('customer_id', $customer->id)
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->latest()
            ->first();

        $pastPrice = 0;

        if ($latestSale) {
            $saleItem = $latestSale->items()->where('product_id', $product->id)->first();

            if ($saleItem) {
                // Format the price with no decimal places
                $pastPrice = floor($saleItem->customer_price);
            }
        }

        return response()->json(['past_price' => $pastPrice]);
    }

    public function destroy($id)
    {
        Sales::find($id)->delete();

        return redirect()->route('admin.sales')->with('success', 'Sales order deleted');
    }
}