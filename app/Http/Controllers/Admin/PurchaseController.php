<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10); // pagination
        $query = Purchase::with(['items', 'supplier', 'user']);
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

    /**
     * Bulk delete purchase orders with enhanced error handling and debugging
     */
    public function bulkDelete(Request $request)
    {
        try {
            // Log the incoming request for debugging
            Log::info('Bulk delete request received', [
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            // Validate the incoming request
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:po,id',
            ]);

            $ids = $request->ids;
            $deletedCount = 0;
            $stockAdjustedCount = 0;
            $paidInvoicesCount = 0;

            Log::info('Validation passed, proceeding with deletion', [
                'ids' => $ids,
                'count' => count($ids),
            ]);

            // Use database transaction for data integrity
            DB::transaction(function () use ($ids, &$deletedCount, &$stockAdjustedCount, &$paidInvoicesCount) {
                // First, get all purchase orders to be deleted for logging
                $purchaseOrders = Purchase::whereIn('id', $ids)->with('items')->get();

                if ($purchaseOrders->isEmpty()) {
                    throw new \Exception('No purchase orders found with the provided IDs');
                }

                Log::info('Found purchase orders to delete', [
                    'found_count' => $purchaseOrders->count(),
                    'po_invoices' => $purchaseOrders->pluck('invoice')->toArray(),
                ]);

                // Separate paid and unpaid purchase orders
                $paidPurchaseOrders = $purchaseOrders->where('status', 'Paid');
                $unpaidPurchaseOrders = $purchaseOrders->where('status', '!=', 'Paid');

                $paidInvoicesCount = $paidPurchaseOrders->count();
                $unpaidInvoicesCount = $unpaidPurchaseOrders->count();

                Log::info('Purchase orders categorized', [
                    'paid_count' => $paidInvoicesCount,
                    'unpaid_count' => $unpaidInvoicesCount,
                    'paid_invoices' => $paidPurchaseOrders->pluck('invoice')->toArray(),
                    'unpaid_invoices' => $unpaidPurchaseOrders->pluck('invoice')->toArray(),
                ]);

                // Log the deletion attempt
                Log::info('Bulk delete purchase orders initiated', [
                    'user_id' => Auth::id(),
                    'po_ids' => $ids,
                    'po_count' => count($ids),
                    'paid_count' => $paidInvoicesCount,
                    'unpaid_count' => $unpaidInvoicesCount,
                ]);

                // Delete related POItems first (if not using CASCADE foreign key)
                $deletedItems = POItem::whereIn('po_id', $ids)->delete();
                Log::info("Deleted {$deletedItems} PO items");

                // Update product stock ONLY for UNPAID purchase orders
                foreach ($unpaidPurchaseOrders as $po) {
                    Log::info('Processing unpaid PO for stock adjustment', [
                        'po_id' => $po->id,
                        'invoice' => $po->invoice,
                        'status' => $po->status,
                    ]);

                    foreach ($po->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            try {
                                $originalStock = null;
                                $newStock = null;

                                // Determine which stock field to use and calculate new stock
                                if (isset($product->stock_quantity)) {
                                    $originalStock = $product->stock_quantity;
                                    $newStock = max(0, $product->stock_quantity - $item->quantity);
                                    $product->update(['stock_quantity' => $newStock]);
                                } elseif (isset($product->quantity)) {
                                    $originalStock = $product->quantity;
                                    $newStock = max(0, $product->quantity - $item->quantity);
                                    $product->update(['quantity' => $newStock]);
                                } elseif (isset($product->stock)) {
                                    $originalStock = $product->stock;
                                    $newStock = max(0, $product->stock - $item->quantity);
                                    $product->update(['stock' => $newStock]);
                                }

                                Log::info('Stock adjusted for unpaid PO', [
                                    'product_id' => $item->product_id,
                                    'po_id' => $po->id,
                                    'invoice' => $po->invoice,
                                    'original_stock' => $originalStock,
                                    'quantity_reduced' => $item->quantity,
                                    'new_stock' => $newStock,
                                ]);

                                $stockAdjustedCount++;
                            } catch (\Exception $e) {
                                Log::warning("Failed to update stock for product {$item->product_id} in unpaid PO {$po->id}: " . $e->getMessage());
                            }
                        }
                    }
                }

                // Log paid purchase orders (stock NOT adjusted)
                foreach ($paidPurchaseOrders as $po) {
                    Log::info('Skipping stock adjustment for paid PO', [
                        'po_id' => $po->id,
                        'invoice' => $po->invoice,
                        'status' => $po->status,
                        'reason' => 'Invoice already paid - stock adjustment not needed',
                    ]);
                }

                // Delete the purchase orders (both paid and unpaid)
                $deletedCount = Purchase::whereIn('id', $ids)->delete();
                Log::info("Successfully deleted {$deletedCount} purchase orders");
            });

            // Return success response with detailed information
            $response = [
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} purchase order(s)",
                'deleted_count' => $deletedCount,
                'stock_adjusted_count' => $stockAdjustedCount,
                'paid_invoices_count' => $paidInvoicesCount,
                'details' => [
                    'paid_invoices' => $paidInvoicesCount,
                    'unpaid_invoices' => $deletedCount - $paidInvoicesCount,
                    'stock_adjustments_made' => $stockAdjustedCount > 0,
                ],
            ];

            Log::info('Bulk delete completed successfully', $response);

            return response()->json($response);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Bulk delete validation failed', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid data provided',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Bulk delete purchase orders failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error deleting purchase orders. Please try again.',
                    'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ],
                500,
            );
        }
    }

    public function bulkMarkPaid(Request $request)
    {
        try {
            // Validate the request - similar to transaction pattern
            $purchaseIds = $request->input('ids', []);

            if (empty($purchaseIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No purchase orders selected.'
                ], 400);
            }

            // Additional validation to ensure all IDs exist
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:purchases,id',
            ]);

            $updatedCount = 0;

            // Get purchase orders by IDs and process them individually
            $purchaseOrders = Purchase::whereIn('id', $purchaseIds)->get();

            foreach ($purchaseOrders as $purchaseOrder) {
                // Skip if already paid - same logic as transactions
                if ($purchaseOrder->status === 'Paid') {
                    continue;
                }

                // Update the purchase order to paid status
                $purchaseOrder->update([
                    'status' => 'Paid',
                    'payment_date' => now(),
                    'updated_at' => now(),
                ]);

                $updatedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} purchase order(s) as paid.",
                'updated_count' => $updatedCount,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['ids'] ?? ['Invalid purchase order IDs']),
            ], 422);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Bulk mark as paid error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating purchase orders.'
            ], 500);
        }
    }

    /**
     * Bulk export purchase orders
     */
    public function bulkExport(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:purchases,id', // Fixed: was 'po,id'
            ]);

            $ids = $request->ids;

            // Get purchase orders with related data
            $pos = Purchase::with(['supplier', 'items.product'])
                ->whereIn('id', $ids)
                ->get();

            // Create CSV content
            $filename = 'purchase_orders_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($pos) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, ['Invoice', 'Supplier', 'Order Date', 'Due Date', 'Status', 'Payment Date', 'Total', 'Items Count']);

                // CSV Data
                foreach ($pos as $po) {
                    fputcsv($file, [$po->invoice, $po->supplier->name ?? 'N/A', $po->order_date, $po->due_date, $po->status, $po->payment_date ?? 'N/A', $po->total, $po->items->count()]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error exporting purchase orders: ' . $e->getMessage()]);
        }
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
        try {
            // Log the incoming request for debugging
            Log::info('Single delete request received', [
                'po_id' => $id,
                'user_id' => Auth::id(),
            ]);

            // Find the purchase order
            $purchase = Purchase::with(['items', 'supplier'])->find($id);

            if (!$purchase) {
                Log::error('Purchase order not found', ['po_id' => $id]);
                return redirect()->route('admin.po')->with('error', 'Purchase order not found');
            }

            $deletedCount = 0;
            $stockAdjustedCount = 0;
            $isPaid = $purchase->status === 'Paid';

            Log::info('Found purchase order to delete', [
                'po_id' => $id,
                'invoice' => $purchase->invoice,
                'status' => $purchase->status,
                'is_paid' => $isPaid,
            ]);

            // Use database transaction for data integrity
            DB::transaction(function () use ($purchase, &$deletedCount, &$stockAdjustedCount, $isPaid) {
                Log::info('Single delete purchase order initiated', [
                    'user_id' => Auth::id(),
                    'po_id' => $purchase->id,
                    'invoice' => $purchase->invoice,
                    'status' => $purchase->status,
                ]);

                // Delete related POItems first (if not using CASCADE foreign key)
                $deletedItems = POItem::where('po_id', $purchase->id)->delete();
                Log::info("Deleted {$deletedItems} PO items for purchase order {$purchase->id}");

                // Update product stock ONLY for UNPAID purchase orders
                if (!$isPaid) {
                    Log::info('Processing unpaid PO for stock adjustment', [
                        'po_id' => $purchase->id,
                        'invoice' => $purchase->invoice,
                        'status' => $purchase->status,
                    ]);

                    foreach ($purchase->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            try {
                                $originalStock = null;
                                $newStock = null;

                                // Determine which stock field to use and calculate new stock
                                if (isset($product->stock_quantity)) {
                                    $originalStock = $product->stock_quantity;
                                    $newStock = max(0, $product->stock_quantity - $item->quantity);
                                    $product->update(['stock_quantity' => $newStock]);
                                } elseif (isset($product->quantity)) {
                                    $originalStock = $product->quantity;
                                    $newStock = max(0, $product->quantity - $item->quantity);
                                    $product->update(['quantity' => $newStock]);
                                } elseif (isset($product->stock)) {
                                    $originalStock = $product->stock;
                                    $newStock = max(0, $product->stock - $item->quantity);
                                    $product->update(['stock' => $newStock]);
                                }

                                Log::info('Stock adjusted for unpaid PO', [
                                    'product_id' => $item->product_id,
                                    'po_id' => $purchase->id,
                                    'invoice' => $purchase->invoice,
                                    'original_stock' => $originalStock,
                                    'quantity_reduced' => $item->quantity,
                                    'new_stock' => $newStock,
                                ]);

                                $stockAdjustedCount++;
                            } catch (\Exception $e) {
                                Log::warning("Failed to update stock for product {$item->product_id} in unpaid PO {$purchase->id}: " . $e->getMessage());
                            }
                        }
                    }
                } else {
                    Log::info('Skipping stock adjustment for paid PO', [
                        'po_id' => $purchase->id,
                        'invoice' => $purchase->invoice,
                        'status' => $purchase->status,
                        'reason' => 'Invoice already paid - stock adjustment not needed',
                    ]);
                }

                // Delete the purchase order
                $deletedCount = $purchase->delete() ? 1 : 0;
                Log::info("Successfully deleted purchase order {$purchase->id}");
            });

            // Prepare success message with details
            $message = "Purchase order deleted successfully";
            if (!$isPaid && $stockAdjustedCount > 0) {
                $message .= " (Stock levels adjusted for {$stockAdjustedCount} products)";
            } elseif ($isPaid) {
                $message .= " (Stock levels unchanged - invoice was paid)";
            }

            Log::info('Single delete completed successfully', [
                'po_id' => $id,
                'deleted_count' => $deletedCount,
                'stock_adjusted_count' => $stockAdjustedCount,
                'was_paid' => $isPaid,
            ]);

            return redirect()->route('admin.po')->with('success', $message);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Single purchase order delete failed', [
                'po_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.po')->with('error', 'Error deleting purchase order. Please try again.');
        }
    }
}
