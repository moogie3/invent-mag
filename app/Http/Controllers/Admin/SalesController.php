<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\User;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $sales = Sales::with(['product', 'customer', 'user'])->paginate($entries);
        $totalinvoice = Sales::count();

        $unpaidDebt = Sales::all()->where('status', 'Unpaid')->sum('total');

        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.sales.index', compact('entries', 'sales', 'totalinvoice', 'shopname', 'address','unpaidDebt'));
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
        $customer = Customer::all();
        $items = SalesItem::all();
        return view('admin.sales.sales-edit', compact('sales', 'customer', 'items'));
    }

    public function store(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'invoice' => 'required|string|unique:sales,invoice',
                'customer_id' => 'required|exists:customers,id',
                'order_date' => 'required|date',
                'products' => 'required|json',
            ]);

            // Check if products are valid JSON
            $products = json_decode($request->products, true);
            if (!$products || !is_array($products)) {
                return back()
                    ->withErrors(['products' => 'Invalid product data'])
                    ->withInput();
            }

            // Calculate total price
            $totalPrice = array_sum(array_column($products, 'total'));

            // Store Purchase Order
            $sales = Sales::create([
                'invoice' => $request->invoice,
                'customer_id' => $request->customer_id,
                'order_date' => $request->order_date,
                'total' => $totalPrice,
            ]);

            // Store Sales items
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

    public function update(Request $request, $id){
        $sales = Sales::findOrFail($id);

        $request->validate([
            'payment_type' => 'required',
            'status' => 'required'
        ]);

        $data = $request->except(["_token", "_method"]);

        if ($request->status === 'Paid') {
        $data['payment_date'] = now();
        }

        $sales->update($data);

        return redirect()->route('admin.sales')->with('success', 'Sales updated successfully!');
    }

    public function getPastPrice(Request $request)
    {
        $customerId = $request->customer_id;
        $productId = $request->product_id;

        $pastPrice = SalesItem::join('sales', 'sales_items.sales_id', '=', 'sales.id')
            ->where('sales.customer_id', $customerId)
            ->where('sales_items.product_id', $productId)
            ->orderBy('sales.order_date', 'desc') // Get the most recent price
            ->value('sales_items.customer_price'); // Get the last price customer paid

        return response()->json(['past_price' => $pastPrice ?? 0]); // Return 0 if no past price found
    }

    public function destroy($id)
    {
        Sales::find($id)->delete();

        return redirect()->route('admin.sales')->with('success', 'Sales order deleted');
    }
}