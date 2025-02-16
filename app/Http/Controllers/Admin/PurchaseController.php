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

    public function edit($id){
        $pos = Purchase::with(['items', 'supplier'])->find($id);
        $suppliers = Supplier::all();
        $items = POItem::all();
        return view('admin.po.purchase-edit', compact('pos','suppliers', 'items'));
    }

    public function store(Request $request){
        try {
            // Validate the request
            $validatedData = $request->validate([
                'invoice'       => 'required|string|unique:po,invoice',
                'supplier_id'   => 'required|exists:suppliers,id',
                'order_date'    => 'required|date',
                'due_date'      => 'required|date',
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
            $purchase = Purchase::create([
                'invoice'       => $request->invoice,
                'supplier_id'   => $request->supplier_id,
                'order_date'    => $request->order_date,
                'due_date'      => $request->due_date,
                'total'         => $totalPrice,
            ]);

            // Store PO items
            foreach ($products as $product) {
                POItem::create([
                    'po_id'        => $purchase->id,
                    'product_id'   => $product['id'],
                    'name'         => $product['name'],
                    'quantity'     => $product['quantity'],
                    'price'        => $product['price'],
                    'total'        => $product['total'],
                ]);

                // Update product price in the products table
                Product::where('id', $product['id'])->update([
                'price' => $product['price']
                ]);
            }

            return redirect()->route('admin.po')->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, $id){
    $pos = Purchase::findOrFail($id);

    $request->validate([
        'payment_type' => 'required',
        'status' => 'required'
    ]);

    $data = $request->except(["_token", "_method"]);
    $pos->update($data);

    return redirect()->route('admin.po')->with('success', 'Purchase updated successfully!');
    }

    public function destroy($id)
    {
        Purchase::find($id)->delete();

        return redirect()->route('admin.po')->with('success', 'Purchase order deleted');
    }


}