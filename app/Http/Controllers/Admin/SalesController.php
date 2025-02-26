<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPrice;
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
        $sales = Sales::with(['product', 'customer','user'])->paginate($entries);
        $totalinvoice = Sales::count();

        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.sales.index', compact('entries','sales','totalinvoice','shopname','address'));
    }

    public function create()
    {
        $sales = Sales::all();
        $customers = Customer::all();
        $products = Product::all();
        return view('admin.sales.sales-create', compact('sales', 'customers', 'products'));
    }

    public function edit($id){
        $sales = Sales::with(['items', 'customers'])->find($id);
        $customers = Customer::all();
        $items = SalesItem::all();
        return view('admin.sales.sales-edit', compact('sales','customers', 'items'));
    }

    public function store(Request $request){
        try {
            // Validate the request
            $validatedData = $request->validate([
                'invoice'       => 'required|string|unique:sales,invoice',
                'customer_id'   => 'required|exists:customers,id',
                'order_date'    => 'required|date',
                'products'      => 'required|json',
            ]);

            // Check if products are valid JSON
            $products = json_decode($request->products, true);
            if (!$products || !is_array($products)) {
                return back()->withErrors(['products' => 'Invalid product data'])->withInput();
            }

            // Calculate total price
            $totalPrice = array_sum(array_column($products, 'total'));

            // Store Purchase Order
            $sales = Sales::create([
                'invoice'       => $request->invoice,
                'customer_id'   => $request->customer_id,
                'order_date'    => $request->order_date,
                'total'         => $totalPrice,
            ]);

            // Store Sales items
            foreach ($products as $product) {
                SalesItem::create([
                    'sales_id'        => $sales->id,
                    'product_id'   => $product['id'],
                    'quantity'     => $product['quantity'],
                    'price'        => $product['price'],
                    'customer_price'=>$product['customer_price'],
                    'total'        => $product['total'],
                ]);

            }
            return redirect()->route('admin.sales')->with('success', 'Sales Order created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])->withInput();
        }
    }
}