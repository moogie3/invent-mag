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

        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.sales.index', compact('entries', 'sales', 'totalinvoice', 'shopname', 'address', 'unpaidDebt'));
    }

    public function create()
    {
        $sales = Sales::all();
        $customers = Customer::all();
        $products = Product::all();
        $items = SalesItem::all();
        $tax = Tax::first();
        $tax = Tax::where('is_active', 1)->first();
        return view('admin.sales.sales-create', compact('sales', 'customers', 'products', 'items','tax'));
    }

    public function edit($id)
    {
        $sales = Sales::with(['items', 'customer'])->find($id);
        $customers = Customer::all();
        $items = SalesItem::all();
        $tax = Tax::where('is_active', 1)->first();
        return view('admin.sales.sales-edit', compact('sales', 'customers', 'items', 'tax'));
    }

    public function view($id)
    {
        $sales = Sales::with(['items', 'customer'])->find($id);
        $customer = Customer::all();
        $items = SalesItem::all();
        $tax = Tax::first();
        return view('admin.sales.sales-view', compact('sales', 'customer', 'items', 'tax'));
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
        $totalDiscount = 0;

        foreach ($products as $product) {
            $quantity = $product['quantity'];
            $price = $product['price']; // Price from the JSON
            $discount = $product['discount'] ?? 0;
            $discountType = $product['discountType'] ?? 'fixed';

            $productSubtotal = $price * $quantity;
            $discountAmount = $discountType === 'percentage'
                ? ($productSubtotal * $discount / 100)
                : $discount;

            $totalDiscount += $discountAmount;
            $subTotal += $productSubtotal;
        }

        // Calculate tax
        $tax = Tax::where('is_active', 1)->first();
        $taxRate = $tax ? $tax->rate : 0;
        $taxAmount = ($subTotal - $totalDiscount) * ($taxRate / 100);
        $grandTotal = $subTotal - $totalDiscount + $taxAmount;

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
            'status' => 'Unpaid', // Default status
        ]);

        // Insert sale items
        foreach ($products as $product) {
            $productSubtotal = $product['price'] * $product['quantity'];
            $discountAmount = $product['discountType'] === 'percentage'
                ? ($productSubtotal * $product['discount'] / 100)
                : $product['discount'];

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

        $request->validate([
            'payment_type' => 'required',
            'status' => 'required',
            'order_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:order_date',
            'products' => 'required|json',
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
        $totalDiscount = 0;

        // Update or create sales items
        foreach ($products as $product) {
            $quantity = $product['quantity'];
            $price = $product['price'];
            $discount = $product['discount'] ?? 0;
            $discountType = $product['discountType'] ?? 'fixed';

            $productSubtotal = $price * $quantity;
            $discountAmount = $discountType === 'percentage'
                ? ($productSubtotal * $discount / 100)
                : $discount;

            $itemTotal = $productSubtotal - $discountAmount;
            $totalDiscount += $discountAmount;
            $subTotal += $productSubtotal;

            // If the product already exists as a sales item, update it
            // Otherwise, create a new sales item
            $salesItem = SalesItem::where('sales_id', $sales->id)
                                ->where('product_id', $product['id'])
                                ->first();

            if ($salesItem) {
                $salesItem->update([
                    'quantity' => $quantity,
                    'customer_price' => $price, // Use customer_price column instead of price
                    'discount' => $discount,
                    'discount_type' => $discountType,
                    'total' => $itemTotal,
                ]);
            } else {
                SalesItem::create([
                    'sales_id' => $sales->id,
                    'product_id' => $product['id'],
                    'quantity' => $quantity,
                    'customer_price' => $price, // Use customer_price column instead of price
                    'discount' => $discount,
                    'discount_type' => $discountType,
                    'total' => $itemTotal,
                ]);
            }
        }

        // Calculate tax
        $tax = Tax::where('is_active', 1)->first();
        $taxRate = $tax ? $tax->rate : 0;
        $taxAmount = ($subTotal - $totalDiscount) * ($taxRate / 100);
        $grandTotal = $subTotal - $totalDiscount + $taxAmount;

        // Update Sales Order
        $sales->update([
            'order_date' => $request->order_date,
            'due_date' => $request->due_date,
            'payment_type' => $request->payment_type,
            'status' => $request->status,
            'tax_rate' => $taxRate,
            'total_tax' => $taxAmount,
            'total' => $grandTotal,
            'payment_date' => $request->status === 'Paid' ? now() : null,
        ]);

        // Remove any sales items that are no longer in the products array
        $existingProductIds = collect($products)->pluck('id')->toArray();
        SalesItem::where('sales_id', $sales->id)
                ->whereNotIn('product_id', $existingProductIds)
                ->delete();

        return redirect()->route('admin.sales.view', $id)->with('success', 'Sales updated successfully.');
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
                      ->whereHas('saleItems', function ($query) use ($product) {
                          $query->where('product_id', $product->id);
                      })
                      ->latest()
                      ->first();

    $pastPrice = 0;

    if ($latestSale) {
        $saleItem = $latestSale->saleItems()
                              ->where('product_id', $product->id)
                              ->first();

        if ($saleItem) {
            $pastPrice = $saleItem->price;
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
