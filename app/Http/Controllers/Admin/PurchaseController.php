<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10); // pagination
        $query = Purchase::with(['product', 'supplier', 'user']);
        // apply filters
        if ($request->has('month') && $request->month) {
            $query->whereMonth('order_date', $request->month);
        }

        if ($request->has('year') && $request->year) {
            $query->whereYear('order_date', $request->year);
        }
        $pos = $query->paginate($entries);
        $totalinvoice = Purchase::count();

        //COUNTING IN INVOICE
        $inCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->count();
        $inCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        //COUNTING OUT INVOICE
        $outCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->count();
        $outCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        //SUMMING TOTAL PURCHASE MONTHLY
        $totalMonthly = Purchase::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');

        //SUMMING TOTAL PAYMENT MONTHLY
        $paymentMonthly = Purchase::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'Paid')->sum('total');

        //USER INFORMATION
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.po.index', compact('inCountamount', 'outCountamount', 'pos', 'inCount', 'outCount', 'shopname', 'address', 'entries', 'totalinvoice', 'totalMonthly', 'paymentMonthly'));
    }

    public function create()
    {
        $pos = Purchase::all();
        $suppliers = Supplier::all();
        $products = Product::all();
        return view('admin.po.purchase-create', compact('pos', 'suppliers', 'products'));
    }

    public function edit($id)
    {
        $pos = Purchase::with(['items', 'supplier'])->find($id);
        $suppliers = Supplier::all();
        $items = POItem::all();
        return view('admin.po.purchase-edit', compact('pos', 'suppliers', 'items'));
    }

    public function view($id)
    {
        $pos = Purchase::with(['items', 'supplier'])->find($id);
        $suppliers = Supplier::all();
        $items = POItem::all();
        return view('admin.po.purchase-view', compact('pos', 'suppliers', 'items'));
    }

    public function store(Request $request)
    {
        try {
            // validate the request
            $validatedData = $request->validate([
                'invoice' => 'required|string|unique:po,invoice',
                'supplier_id' => 'required|exists:suppliers,id',
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

            // store Purchase Order
            $purchase = Purchase::create([
                'invoice' => $request->invoice,
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'due_date' => $request->due_date,
                'total' => $totalPrice,
            ]);

            // store PO items
            foreach ($products as $product) {
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'discount' => $product['discount'] ?? 0,
                    'discount_type' => $product['discountType'] ?? 'percentage',
                    'total' => $product['total'],
                ]);

                // update product price in the products table
                Product::where('id', $product['id'])->update([
                    'price' => $product['price'],
                ]);
            }

            return redirect()->route('admin.po')->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, $id)
{
    try {
        $po = Purchase::findOrFail($id);

        // Update payment type and status
        $po->payment_type = $request->payment_type;
        $po->status = $request->status;

        if ($request->status === 'Paid') {
            $po->payment_date = now(); // Automatically set payment date if paid
        } else {
            $po->payment_date = null; // Reset if marked unpaid again
        }

        $totalAmount = 0;

        // Update each item
        foreach ($request->items as $itemId => $itemData) {
            $poItem = POItem::findOrFail($itemId);

            $quantity = (int) $itemData['quantity'];
            $price = (int) $itemData['price'];
            $discount = (float) $itemData['discount'];
            $discountType = $itemData['discountType'] ?? 'percentage';

            $totalBeforeDiscount = $quantity * $price;
            $discountAmount = $discountType === 'percentage'
                ? ($totalBeforeDiscount * $discount) / 100
                : $discount;

            $itemTotal = $totalBeforeDiscount - $discountAmount;

            $poItem->update([
                'quantity' => $quantity,
                'price' => $price,
                'discount' => $discount,
                'discount_type' => $discountType,
                'total' => floor($itemTotal),
            ]);

            // Optional: update product purchase price if needed
            Product::where('id', $poItem->product_id)->update([
                'price' => $price,
            ]);

            $totalAmount += $itemTotal;
        }

        // Update PO total
        $po->total = floor($totalAmount);
        $po->save();

        return redirect()->route('admin.po.view', $po->id)->with('success', 'Purchase order updated successfully.');
    } catch (\Exception $e) {
        return back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])->withInput();
    }
}


    public function destroy($id)
    {
        Purchase::find($id)->delete();

        return redirect()->route('admin.po')->with('success', 'Purchase order deleted');
    }
}
