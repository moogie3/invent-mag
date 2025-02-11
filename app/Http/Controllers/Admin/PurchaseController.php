<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    public function index()
    {
        $pos = Purchase::with(['product', 'supplier'])->get();
        return view('admin.po.index', ['pos' => $pos]);
    }

    public function create()
    {
        $pos = Purchase::all();
        $suppliers = Supplier::all();
        $products = Product::all();
        return view('admin.po.purchase-create', compact('pos', 'suppliers', 'products'));
    }

    public function store(Request $request)
{
    try {
        // Validate form input
        $validatedData = $request->validate([
            'invoice'       => 'required|string|unique:po,invoice',
            'supplier_id'   => 'required|exists:suppliers,id',
            'order_date'    => 'required|date',
            'due_date'      => 'required|date',
            'payment_type'  => 'required|string',
            'status'        => 'required|string',
            'products'      => 'required|json',
        ]);

        // Decode JSON products array
        $products = json_decode($request->products, true);

        if (!$products || !is_array($products)) {
            return back()->with('error', 'Invalid product data.');
        }

        // Calculate total price of all products
        $totalPrice = array_sum(array_column($products, 'total'));

        // Store data into `po` table
        $purchase = Purchase::create([
            'invoice'       => $request->invoice,
            'supplier_id'   => $request->supplier_id,
            'order_date'    => $request->order_date,
            'due_date'      => $request->due_date,
            'payment_type'  => $request->payment_type,
            'status'        => $request->status,
            'total'         => $totalPrice,
        ]);

        // Store each product into `po_items` table
        foreach ($products as $product) {
            POItem::create([
                'po_id'        => $purchase->id,
                'product_id'   => $product['id'],
                'product_name' => $product['name'],
                'quantity'     => $product['quantity'],
                'price'        => $product['price'],
                'total'        => $product['total'],
            ]);
        }

        return redirect()->route('admin.po.index')->with('success', 'Purchase Order created successfully.');
    } catch (\Exception $e) {
        return back()->with('error', 'Error: ' . $e->getMessage());
    }
}

}