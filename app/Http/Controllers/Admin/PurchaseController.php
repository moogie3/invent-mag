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
        $paymentMonthly = Purchase::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->where('status', 'Paid')->sum('total');

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

        // Instead of redirecting, we'll pass a flag to the view
        $isPaid = $pos->status == 'Paid';

        return view('admin.po.purchase-edit', compact('pos', 'suppliers', 'items', 'isPaid'));
    }

    public function view($id)
    {
        $pos = Purchase::with(['items', 'supplier'])->find($id);
        $suppliers = Supplier::all();
        $items = POItem::all();
        return view('admin.po.purchase-view', compact('pos', 'suppliers', 'items'));
    }

    public function modalView($id)
    {
        $pos = Purchase::with(['supplier', 'items.product'])->findOrFail($id);

        return view('admin.layouts.modals.pomodals-view', compact('pos'));
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
                'discount_total' => 'nullable|numeric',
                'discount_total_type' => 'nullable|in:fixed,percentage',
            ]);

            // check if products are valid JSON
            $products = json_decode($request->products, true);
            if (!$products || !is_array($products)) {
                return back()
                    ->withErrors(['products' => 'Invalid product data'])
                    ->withInput();
            }

            // calculate total price and product discounts
            $subtotal = 0;
            $totalProductDiscounts = 0;

            foreach ($products as $product) {
                // Use PurchaseHelper to calculate discounts and final amount
                $discountPerUnit = \App\Helpers\PurchaseHelper::calculateDiscountPerUnit($product['price'], $product['discount'], $product['discountType']);

                $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal($product['price'], $product['quantity'], $product['discount'], $product['discountType']);

                $subtotal += $finalAmount;
                $totalProductDiscounts += $discountPerUnit * $product['quantity'];
            }

            // Apply order discount if present
            $orderDiscountValue = $request->discount_total ?? 0;
            $orderDiscountType = $request->discount_total_type ?? 'fixed';
            $orderDiscountAmount = \App\Helpers\PurchaseHelper::calculateDiscount($subtotal, $orderDiscountValue, $orderDiscountType);

            // Final total
            $finalTotal = $subtotal - $orderDiscountAmount;

            // store Purchase Order
            $purchase = Purchase::create([
                'invoice' => $request->invoice,
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'due_date' => $request->due_date,
                'total' => floor($finalTotal),
                'discount_total' => $orderDiscountValue,
                'discount_total_type' => $orderDiscountType,
                'total_discount' => $totalProductDiscounts,
            ]);

            // store PO items
            foreach ($products as $product) {
                $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal($product['price'], $product['quantity'], $product['discount'], $product['discountType']);

                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'discount' => $product['discount'] ?? 0,
                    'discount_type' => $product['discountType'] ?? 'percentage',
                    'total' => floor($finalAmount),
                ]);

                $productModel = Product::find($product['id']);
                if ($productModel) {
                    // Use the correct field name based on your database
                    if (isset($productModel->stock_quantity)) {
                        $productModel->increment('stock_quantity', $product['quantity']);
                    } elseif (isset($productModel->quantity)) {
                        $productModel->increment('quantity', $product['quantity']);
                    } elseif (isset($productModel->stock)) {
                        $productModel->increment('stock', $product['quantity']);
                    }
                }

                // update product price in the products table
                Product::where('id', $product['id'])->update([
                    'price' => $product['price'],
                ]);
            }

            return redirect()->route('admin.po.create')->with('success', 'Purchase Order created successfully.');
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

            // Basic PO fields
            $po->payment_type = $request->payment_type;
            $po->status = $request->status;

            $po->payment_date = $request->status === 'Paid' ? now() : null;
            $po->discount_total = $request->discount_total ?? 0;
            $po->discount_total_type = $request->discount_total_type ?? 'fixed';

            $subtotal = 0;
            $totalProductDiscounts = 0;

            foreach ($request->items as $itemId => $itemData) {
                $poItem = POItem::findOrFail($itemId);

                $quantity = (int) $itemData['quantity'];
                $price = (int) $itemData['price'];
                $discount = (float) $itemData['discount'];
                $discountType = $itemData['discountType'] ?? 'percentage';

                // Use PurchaseHelper to calculate discounts and final amount
                $discountPerUnit = \App\Helpers\PurchaseHelper::calculateDiscountPerUnit($price, $discount, $discountType);

                $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal($price, $quantity, $discount, $discountType);

                $totalProductDiscounts += $discountPerUnit * $quantity;

                $poItem->update([
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => $discount,
                    'discount_type' => $discountType,
                    'total' => floor($finalAmount),
                ]);

                // Optional: update product base price
                Product::where('id', $poItem->product_id)->update([
                    'price' => $price,
                ]);

                $subtotal += $finalAmount;
            }

            $orderDiscount = \App\Helpers\PurchaseHelper::calculateDiscount($subtotal, $po->discount_total, $po->discount_total_type);

            $po->total = floor($subtotal - $orderDiscount);
            $po->save();

            return redirect()->route('admin.po.view', $po->id)->with('success', 'Purchase order updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }
    public function destroy($id)
    {
        Purchase::find($id)->delete();

        return redirect()->route('admin.po')->with('success', 'Purchase order deleted');
    }
}
