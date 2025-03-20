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
        return view('admin.sales.sales-edit', compact('sales', 'customers', 'items','tax'));
    }

    public function view($id)
    {
        $sales = Sales::with(['items', 'customer'])->find($id);
        $customer = Customer::all();
        $items = SalesItem::all();
        $tax = Tax::where('is_active', 1)->first();
        return view('admin.sales.sales-view', compact('sales', 'customer', 'items','tax'));
    }

    public function store(Request $request)
    {
        try {
            // validate the request
            $validatedData = $request->validate([
                'invoice' => 'required|string|unique:sales,invoice',
                'customer_id' => 'required|exists:customers,id',
                'order_date' => 'required|date',
                'due_date' => 'required|date',
                'products' => 'required|json',
            ]);

            // check if products are valid JSON
            $products = json_decode($request->products, true);
            if (!$products || !is_array($products)) {
                return back()
                    ->withErrors(['products' => 'Invalid product data'])
                    ->withInput();
            }

            // calculate total price
            $totalPrice = array_sum(array_column($products, 'total'));

            // store sales
            $sales = Sales::create([
                'invoice' => $request->invoice,
                'customer_id' => $request->customer_id,
                'order_date' => $request->order_date,
                'due_date' => $request->due_date,
                'total' => $totalPrice,
            ]);

            // store sales items
            foreach ($products as $product) {
                SalesItem::create([
                    'sales_id' => $sales->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'customer_price' => $product['customer_price'],
                    'total' => $product['total'],
                ]);
            }
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
    $totalAmount = 0;

    $request->validate([
        'payment_type' => 'required',
        'status' => 'required',
        'order_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:order_date',
    ]);

    // Fetch active tax (if any)
    $tax = Tax::where('is_active', 1)->first();
    $taxRate = $tax ? $tax->rate : 0; // Default to 0 if no tax

    foreach ($request->items as $itemId => $itemData) {
        $salesItem = SalesItem::findOrFail($itemId);
        $quantity = $itemData['quantity'];
        $price = $itemData['price'];
        $discount = $itemData['discount'] ?? 0; // Default to 0 if not provided

        // Calculate item total with discount
        $discountAmount = ($price * $discount) / 100;
        $itemTotal = $quantity * ($price - $discountAmount);

        // Update sales item
        $salesItem->quantity = $quantity;
        $salesItem->price = $price;
        $salesItem->discount = $discount;
        $salesItem->total = floor($itemTotal); // No decimals
        $salesItem->save();

        // Add to total sales amount
        $totalAmount += $itemTotal;
    }

    // Calculate tax amount
    $taxAmount = ($totalAmount * $taxRate) / 100;
    $totalWithTax = $totalAmount + $taxAmount;

    // Update Sales record
    $sales->total = floor($totalWithTax); // Total amount with tax
    $sales->tax_amount = floor($taxAmount); // Store tax amount separately
    $sales->order_date = $request->order_date;
    $sales->due_date = $request->due_date;

    if ($request->status === 'Paid') {
        $sales->payment_date = now();
    }

    $sales->payment_type = $request->payment_type;
    $sales->status = $request->status;
    $sales->save();

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
