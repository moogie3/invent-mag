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
        return view('admin.sales.sales-create', compact('sales', 'customers', 'products', 'items'));
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
        $tax = Tax::where('is_active', 1)->first();
        return view('admin.sales.sales-view', compact('sales', 'customer', 'items', 'tax'));
    }

    public function store(Request $request)
    {
        try {
            // Validate request
            $validatedData = $request->validate([
                'invoice' => 'required|string|unique:sales,invoice',
                'customer_id' => 'required|exists:customers,id',
                'order_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:order_date',
                'products' => 'required|json',
            ]);

            // Decode products JSON safely
            $products = json_decode($request->products, true);
            if (!is_array($products)) {
                return back()
                    ->withErrors(['products' => 'Invalid product data'])
                    ->withInput();
            }

            // Initialize totals
            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            // Create Sales Order (initially with zero total)
            $sales = Sales::create([
                'invoice' => $request->invoice,
                'customer_id' => $request->customer_id,
                'order_date' => $request->order_date,
                'due_date' => $request->due_date,
                'total' => 0, // Placeholder, updated later
            ]);

            // Store Sales Items
            foreach ($products as $product) {
                $quantity = $product['quantity'];
                $price = $product['price'];
                $discountValue = $product['discount'] ?? 0;
                $discountType = $product['discount_type'] ?? 'fixed';
                $taxRate = $product['tax_rate'] ?? 0;

                // Calculate Discount
                $discountAmount = $discountType === 'percentage' ? $quantity * $price * ($discountValue / 100) : $discountValue;

                // Calculate Item Total (after discount)
                $itemTotal = $quantity * $price - $discountAmount;
                $totalDiscount += $discountAmount;

                // Calculate Tax (after discount is applied)
                $taxAmount = ($taxRate / 100) * $itemTotal;
                $totalTax += $taxAmount;

                // Save Sales Item
                SalesItem::create([
                    'sales_id' => $sales->id,
                    'product_id' => $product['id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'customer_price' => $product['customer_price'] ?? $price,
                    'discount' => $discountValue,
                    'discount_type' => $discountType,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => floor($itemTotal + $taxAmount),
                ]);

                // Accumulate subtotal
                $subtotal += $quantity * $price;
            }

            // Final Total Calculation
            $finalTotal = $subtotal - $totalDiscount + $totalTax;

            // Update Sales Order with Final Totals
            $sales->update([
                'total' => floor($finalTotal),
                'subtotal' => floor($subtotal),
                'discount_total' => floor($totalDiscount),
                'tax_total' => floor($totalTax),
            ]);

            return redirect()->route('admin.sales')->with('success', 'Sales Order created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, $id)
{
    $sales = Sales::findOrFail($id);

    $request->validate([
        'payment_type' => 'required',
        'status' => 'required',
        'order_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:order_date',
        'total' => 'required|numeric|min:0',
        'items' => 'required|array', // Ensure items array exists
    ]);

    $totalAmount = 0;
    $totalDiscount = 0;

    foreach ($request->items as $itemId => $itemData) {
        $salesItem = SalesItem::findOrFail($itemId);
        $quantity = $itemData['quantity'];
        $price = $itemData['price'];
        $discountValue = $itemData['discount'] ?? 0;
        $discountType = $itemData['discount_type'] ?? 'fixed';

        // Calculate Discount
        $discountAmount = $discountType === 'percentage'
            ? $quantity * $price * ($discountValue / 100)
            : $discountValue;
        $totalDiscount += $discountAmount;

        // Calculate Item Total (after discount)
        $itemTotal = ($quantity * $price) - $discountAmount;

        // Update Sales Item (fixing missing fields)
        $salesItem->update([
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discountValue,
            'discount_type' => $discountType,
            'total' => floor($itemTotal + $salesItem->tax_amount), // Keep tax unchanged
        ]);

        // Accumulate subtotal
        $totalAmount += $quantity * $price;
    }

    // Final Total Calculation
    $finalTotal = $totalAmount - $totalDiscount + $sales->total_tax;

    // Update Sales Order
    $sales->update([
        'total' => floor($finalTotal),
        'subtotal' => floor($totalAmount),
        'discount_total' => floor($totalDiscount),
        'order_date' => $request->order_date,
        'due_date' => $request->due_date,
        'payment_type' => $request->payment_type,
        'status' => $request->status,
        'payment_date' => $request->status === 'Paid' ? now() : null,
    ]);

    return redirect()->route('admin.sales.view', $id)->with('success', 'Sales updated successfully.');
}

    public function getPastPrice(Request $request)
    {
        $customerId = $request->customer_id;
        $productId = $request->product_id;

        $pastPrice = SalesItem::join('sales', 'sales_items.sales_id', '=', 'sales.id')
            ->where('sales.customer_id', $customerId)
            ->where('sales_items.product_id', $productId)
            ->orderBy('sales.order_date', 'desc') // get the most recent price
            ->value('sales_items.customer_price'); // get the last price customer paid

        return response()->json(['past_price' => $pastPrice ?? 0]); // return 0 if no past price found
    }

    public function destroy($id)
    {
        Sales::find($id)->delete();

        return redirect()->route('admin.sales')->with('success', 'Sales order deleted');
    }
}
