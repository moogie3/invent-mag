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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $totalMonthly = Sales::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');

        // Count pending orders - defining "Pending" status
        $pendingOrders = Sales::where('status', 'Unpaid')->count();
        $dueInvoices = Sales::where('status', 'Unpaid')
            ->whereDate('due_date', '>=', now()) // Due date is today or later
            ->whereDate('due_date', '<=', now()->addDays(7)) // Due date is within 30 days
            ->count();
        $posTotal = Sales::where('is_pos', true)->sum('total');
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.sales.index', compact('posTotal', 'dueInvoices', 'entries', 'sales', 'totalinvoice', 'shopname', 'address', 'unpaidDebt', 'pendingOrders', 'totalMonthly'));
    }
    public function create()
    {
        $sales = Sales::all();
        $customers = Customer::all();
        $products = Product::all();
        $items = SalesItem::all();
        $tax = Tax::first();
        $tax = Tax::where('is_active', 1)->first();
        return view('admin.sales.sales-create', compact('sales', 'customers', 'products', 'items', 'tax'));
    }
    public function edit($id)
    {
        $sales = Sales::with(['items', 'customer'])->find($id);
        $customers = Customer::all();
        $items = SalesItem::all();
        $tax = Tax::where('is_active', 1)->first();
        $isPaid = $sales->status == 'Paid';
        return view('admin.sales.sales-edit', compact('sales', 'customers', 'items', 'tax', 'isPaid'));
    }

    public function view($id)
{
    $sales = Sales::with(['items', 'customer'])->find($id);

    // Check if this is a POS invoice
    if (strpos($sales->invoice, 'POS-') === 0) {
        // If it's a POS invoice, redirect to the receipt view
        return redirect()->route('admin.pos.receipt', $id);
    }

    // For regular invoices, continue with the existing code
    $customer = Customer::all();
    $items = SalesItem::all();
    $tax = Tax::first();

    // Calculate summary data similar to PurchaseController
    $itemCount = $sales->items->count();
    $subtotal = 0;
    $totalItemDiscount = 0;

    // Calculate subtotal and item discounts
    foreach ($sales->items as $item) {
        $itemSubtotal = $item->customer_price * $item->quantity;

        // Calculate item discount
        if ($item->discount_type === 'percentage') {
            $itemDiscountAmount = ($itemSubtotal * $item->discount) / 100;
        } else {
            $itemDiscountAmount = $item->discount * $item->quantity;
        }

        $totalItemDiscount += $itemDiscountAmount;
        $subtotal += ($itemSubtotal - $itemDiscountAmount);
    }

    // Calculate order discount
    $orderDiscount = 0;
    if ($sales->order_discount > 0) {
        if ($sales->order_discount_type === 'percentage') {
            $orderDiscount = ($subtotal * $sales->order_discount) / 100;
        } else {
            $orderDiscount = $sales->order_discount;
        }
    }

    // Calculate tax (already stored in sales record)
    $taxAmount = $sales->total_tax;
    $finalTotal = $sales->total;

    // Create summary array like PurchaseController
    $summary = [
        'itemCount' => $itemCount,
        'subtotal' => $subtotal,
        'totalItemDiscount' => $totalItemDiscount,
        'orderDiscount' => $orderDiscount,
        'taxAmount' => $taxAmount,
        'finalTotal' => $finalTotal
    ];

    return view('admin.sales.sales-view', compact(
        'sales',
        'customer',
        'items',
        'tax',
        'summary',
        'itemCount',
        'subtotal',
        'orderDiscount',
        'finalTotal',
        'totalItemDiscount',
        'taxAmount'
    ));
}

    public function modalViews($id)
    {
        try {
            $sales = Sales::with(['customer', 'items.product'])->findOrFail($id);
            return view('admin.layouts.modals.salesmodals-view', compact('sales'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Sales record not found for modal view: {$id}");
            return response('<div class="alert alert-danger">Sales record not found.</div>', 404);
        } catch (\Exception $e) {
            Log::error("Error loading sales modal view for ID {$id}: " . $e->getMessage());
            return response('<div class="alert alert-danger">Error loading sales details: ' . $e->getMessage() . '</div>', 500);
        }
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
                'discount_total' => 'nullable|numeric|min:0',
                'discount_total_type' => 'nullable|in:fixed,percentage',
            ]);

            // Decode products
            $products = json_decode($request->products, true);
            if (!$products || !is_array($products)) {
                return back()
                    ->withErrors(['products' => 'Invalid product data'])
                    ->withInput();
            }

            $subTotal = 0;
            $itemsData = [];

            foreach ($products as $product) {
                $quantity = $product['quantity'];
                $price = $product['price'];
                $discount = $product['discount'] ?? 0;
                $discountType = $product['discountType'] ?? 'fixed';

                $productSubtotal = $price * $quantity;

                // Calculate item discount consistently
                if ($discountType === 'percentage') {
                    $itemDiscountAmount = ($productSubtotal * $discount) / 100;
                } else {
                    // For fixed discount, apply per unit then multiply by quantity
                    $itemDiscountAmount = $discount * $quantity;
                }

                $itemTotal = $productSubtotal - $itemDiscountAmount;
                $subTotal += $itemTotal;

                $itemsData[] = [
                    'product' => $product,
                    'productSubtotal' => $productSubtotal,
                    'itemDiscountAmount' => $itemDiscountAmount,
                    'itemTotal' => $itemTotal,
                ];
            }

            // Step 2: Calculate order discount (apply to subtotal after item discounts)
            $orderDiscount = $request->discount_total ?? 0;
            $orderDiscountType = $request->discount_total_type ?? 'fixed';

            if ($orderDiscountType === 'percentage') {
                $orderDiscountAmount = ($subTotal * $orderDiscount) / 100;
            } else {
                $orderDiscountAmount = $orderDiscount;
            }

            // Step 3: Calculate tax on amount after all discounts
            $taxableAmount = $subTotal - $orderDiscountAmount;
            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;
            $taxAmount = $taxableAmount * ($taxRate / 100);

            // Step 4: Calculate grand total
            $grandTotal = $taxableAmount + $taxAmount;

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
                'order_discount' => $orderDiscount, // Store the original discount value
                'order_discount_type' => $orderDiscountType,
                'status' => 'Unpaid',
                'is_pos' => 0,
            ]);

            // Insert sale items using the calculated data
            foreach ($itemsData as $index => $itemData) {
                $product = $itemData['product'];

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'customer_price' => $product['price'],
                    'discount' => $product['discount'] ?? 0,
                    'discount_type' => $product['discountType'] ?? 'fixed',
                    'total' => $itemData['itemTotal'],
                ]);

                // Update product stock
                $productModel = Product::find($product['id']);
                if ($productModel) {
                    if (isset($productModel->stock_quantity)) {
                        $productModel->decrement('stock_quantity', $product['quantity']);
                    } elseif (isset($productModel->quantity)) {
                        $productModel->decrement('quantity', $product['quantity']);
                    } elseif (isset($productModel->stock)) {
                        $productModel->decrement('stock', $product['quantity']);
                    }
                }
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

            // Basic sales fields update
            $sales->payment_type = $request->payment_type;
            $sales->status = $request->status;
            $sales->payment_date = $request->status === 'Paid' ? now() : null;
            $sales->order_discount_type = $request->order_discount_type ?? 'fixed'; // FIXED: removed extra '_type'
            $sales->order_discount = $request->order_discount ?? 0;

            $subTotal = 0;
            $itemDiscountTotal = 0;

            // Process items directly from request
            if (isset($request->items) && is_array($request->items)) {
                foreach ($request->items as $itemId => $itemData) {
                    $salesItem = SalesItem::findOrFail($itemId);

                    $quantity = (int) $itemData['quantity'];
                    $price = (float) $itemData['price'];
                    $discount = (float) $itemData['discount'];
                    $discountType = $itemData['discount_type'] ?? 'fixed';

                    // Calculate discount amount
                    $discountAmount = $discountType === 'percentage' ? (($price * $discount) / 100) * $quantity : $discount * $quantity;

                    $productSubtotal = $price * $quantity;
                    $itemTotal = $productSubtotal - $discountAmount;

                    $itemDiscountTotal += $discountAmount;
                    $subTotal += $itemTotal;

                    // Update the sales item
                    $salesItem->update([
                        'quantity' => $quantity,
                        'customer_price' => $price,
                        'discount' => $discount,
                        'discount_type' => $discountType,
                        'total' => $itemTotal,
                    ]);
                }
            }

            // Calculate order discount
            $orderDiscountAmount = $sales->order_discount_type === 'percentage' ? ($subTotal * $sales->order_discount) / 100 : $sales->order_discount;

            // Calculate tax using the EXISTING tax rate from the sales record
            $taxable = $subTotal - $orderDiscountAmount;
            $taxRate = $sales->tax_rate; // Use the stored tax rate instead of fetching a new one
            $taxAmount = $taxable * ($taxRate / 100);

            // Set final values - don't update the tax_rate field as we're keeping the historical rate
            $sales->total_tax = $taxAmount;
            $sales->total = $taxable + $taxAmount;
            $sales->save();

            return redirect()->route('admin.sales.view', $id)->with('success', 'Sale updated successfully.');
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
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->latest()
            ->first();

        $pastPrice = 0;

        if ($latestSale) {
            $saleItem = $latestSale->items()->where('product_id', $product->id)->first();

            if ($saleItem) {
                // Format the price with no decimal places
                $pastPrice = floor($saleItem->customer_price);
            }
        }

        return response()->json(['past_price' => $pastPrice]);
    }

    public function bulkDelete(Request $request)
    {
        try {
            // Log the incoming request for debugging
            Log::info('Bulk delete sales request received', [
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            // Validate the incoming request
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:sales,id',
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
                // First, get all sales orders to be deleted for logging
                $salesOrders = Sales::whereIn('id', $ids)->with('items')->get();

                if ($salesOrders->isEmpty()) {
                    throw new \Exception('No sales orders found with the provided IDs');
                }

                Log::info('Found sales orders to delete', [
                    'found_count' => $salesOrders->count(),
                    'sales_invoices' => $salesOrders->pluck('invoice')->toArray(),
                ]);

                // Separate paid and unpaid sales orders
                $paidSalesOrders = $salesOrders->where('status', 'Paid');
                $unpaidSalesOrders = $salesOrders->where('status', '!=', 'Paid');

                $paidInvoicesCount = $paidSalesOrders->count();
                $unpaidInvoicesCount = $unpaidSalesOrders->count();

                Log::info('Sales orders categorized', [
                    'paid_count' => $paidInvoicesCount,
                    'unpaid_count' => $unpaidInvoicesCount,
                    'paid_invoices' => $paidSalesOrders->pluck('invoice')->toArray(),
                    'unpaid_invoices' => $unpaidSalesOrders->pluck('invoice')->toArray(),
                ]);

                // Log the deletion attempt
                Log::info('Bulk delete sales orders initiated', [
                    'user_id' => Auth::id(),
                    'sales_ids' => $ids,
                    'sales_count' => count($ids),
                    'paid_count' => $paidInvoicesCount,
                    'unpaid_count' => $unpaidInvoicesCount,
                ]);

                // Delete related SalesItems first (if not using CASCADE foreign key)
                $deletedItems = SalesItem::whereIn('sales_id', $ids)->delete();
                Log::info("Deleted {$deletedItems} sales items");

                // Update product stock ONLY for UNPAID sales orders
                foreach ($unpaidSalesOrders as $sale) {
                    Log::info('Processing unpaid sale for stock adjustment', [
                        'sale_id' => $sale->id,
                        'invoice' => $sale->invoice,
                        'status' => $sale->status,
                    ]);

                    foreach ($sale->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            try {
                                $originalStock = null;
                                $newStock = null;

                                // Determine which stock field to use and calculate new stock
                                // For sales, we ADD back the quantity (reverse the sale)
                                if (isset($product->stock_quantity)) {
                                    $originalStock = $product->stock_quantity;
                                    $newStock = $product->stock_quantity + $item->quantity;
                                    $product->update(['stock_quantity' => $newStock]);
                                } elseif (isset($product->quantity)) {
                                    $originalStock = $product->quantity;
                                    $newStock = $product->quantity + $item->quantity;
                                    $product->update(['quantity' => $newStock]);
                                } elseif (isset($product->stock)) {
                                    $originalStock = $product->stock;
                                    $newStock = $product->stock + $item->quantity;
                                    $product->update(['stock' => $newStock]);
                                }

                                Log::info('Stock adjusted for unpaid sale', [
                                    'product_id' => $item->product_id,
                                    'sale_id' => $sale->id,
                                    'invoice' => $sale->invoice,
                                    'original_stock' => $originalStock,
                                    'quantity_restored' => $item->quantity,
                                    'new_stock' => $newStock,
                                ]);

                                $stockAdjustedCount++;
                            } catch (\Exception $e) {
                                Log::warning("Failed to update stock for product {$item->product_id} in unpaid sale {$sale->id}: " . $e->getMessage());
                            }
                        }
                    }
                }

                // Log paid sales orders (stock NOT adjusted)
                foreach ($paidSalesOrders as $sale) {
                    Log::info('Skipping stock adjustment for paid sale', [
                        'sale_id' => $sale->id,
                        'invoice' => $sale->invoice,
                        'status' => $sale->status,
                        'reason' => 'Invoice already paid - stock adjustment not needed',
                    ]);
                }

                // Delete the sales orders (both paid and unpaid)
                $deletedCount = Sales::whereIn('id', $ids)->delete();
                Log::info("Successfully deleted {$deletedCount} sales orders");
            });

            // Return success response with detailed information
            $response = [
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} sales order(s)",
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
            Log::error('Bulk delete sales orders failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error deleting sales orders. Please try again.',
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
            $salesIds = $request->input('ids', []);

            if (empty($salesIds)) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No sales orders selected.',
                    ],
                    400,
                );
            }

            // Additional validation to ensure all IDs exist
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:sales,id',
            ]);

            $updatedCount = 0;

            // Get sales orders by IDs and process them individually
            $salesOrders = Sales::whereIn('id', $salesIds)->get();

            foreach ($salesOrders as $salesOrder) {
                // Skip if already paid - same logic as transactions
                if ($salesOrder->status === 'Paid') {
                    continue;
                }

                // Update the sales order to paid status
                $salesOrder->update([
                    'status' => 'Paid',
                    'payment_date' => now(),
                    'updated_at' => now(),
                ]);

                $updatedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} sales order(s) as paid.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $e->errors()['ids'] ?? ['Invalid sales order IDs']),
                ],
                422,
            );
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Bulk mark as paid error: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while updating sales orders.',
                ],
                500,
            );
        }
    }

    public function bulkExport(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:sales,id',
            ]);

            $ids = $request->ids;

            // Get sales orders with related data
            $sales = Sales::with(['customer', 'items.product'])
                ->whereIn('id', $ids)
                ->get();

            // Create CSV content
            $filename = 'sales_orders_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($sales) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, ['Invoice', 'Customer', 'Order Date', 'Due Date', 'Status', 'Payment Date', 'Total', 'Items Count']);

                // CSV Data
                foreach ($sales as $sale) {
                    fputcsv($file, [$sale->invoice, $sale->customer->name ?? 'N/A', $sale->order_date, $sale->due_date, $sale->status, $sale->payment_date ?? 'N/A', $sale->total, $sale->items->count()]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error exporting sales orders: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            // Log the incoming request for debugging
            Log::info('Single delete sales request received', [
                'sales_id' => $id,
                'user_id' => Auth::id(),
            ]);

            // Use database transaction for data integrity
            DB::transaction(function () use ($id) {
                // Get the sales order to be deleted
                $salesOrder = Sales::with('items')->find($id);

                if (!$salesOrder) {
                    throw new \Exception("Sales order with ID {$id} not found");
                }

                Log::info('Found sales order to delete', [
                    'sales_id' => $salesOrder->id,
                    'invoice' => $salesOrder->invoice,
                    'status' => $salesOrder->status,
                ]);

                // Check if the sales order is paid or unpaid
                $isPaid = $salesOrder->status === 'Paid';

                Log::info('Sales order categorized', [
                    'sales_id' => $salesOrder->id,
                    'invoice' => $salesOrder->invoice,
                    'status' => $salesOrder->status,
                    'is_paid' => $isPaid,
                ]);

                // Log the deletion attempt
                Log::info('Delete sales order initiated', [
                    'user_id' => Auth::id(),
                    'sales_id' => $id,
                    'invoice' => $salesOrder->invoice,
                    'is_paid' => $isPaid,
                ]);

                // Delete related SalesItems first (if not using CASCADE foreign key)
                $deletedItems = SalesItem::where('sales_id', $id)->delete();
                Log::info("Deleted {$deletedItems} sales items for sales order {$id}");

                // Update product stock ONLY for UNPAID sales orders
                if (!$isPaid) {
                    Log::info('Processing unpaid sale for stock adjustment', [
                        'sale_id' => $salesOrder->id,
                        'invoice' => $salesOrder->invoice,
                        'status' => $salesOrder->status,
                    ]);

                    foreach ($salesOrder->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            try {
                                $originalStock = null;
                                $newStock = null;

                                // Determine which stock field to use and calculate new stock
                                // For sales, we ADD back the quantity (reverse the sale)
                                if (isset($product->stock_quantity)) {
                                    $originalStock = $product->stock_quantity;
                                    $newStock = $product->stock_quantity + $item->quantity;
                                    $product->update(['stock_quantity' => $newStock]);
                                } elseif (isset($product->quantity)) {
                                    $originalStock = $product->quantity;
                                    $newStock = $product->quantity + $item->quantity;
                                    $product->update(['quantity' => $newStock]);
                                } elseif (isset($product->stock)) {
                                    $originalStock = $product->stock;
                                    $newStock = $product->stock + $item->quantity;
                                    $product->update(['stock' => $newStock]);
                                }

                                Log::info('Stock adjusted for unpaid sale', [
                                    'product_id' => $item->product_id,
                                    'sale_id' => $salesOrder->id,
                                    'invoice' => $salesOrder->invoice,
                                    'original_stock' => $originalStock,
                                    'quantity_restored' => $item->quantity,
                                    'new_stock' => $newStock,
                                ]);
                            } catch (\Exception $e) {
                                Log::warning("Failed to update stock for product {$item->product_id} in unpaid sale {$salesOrder->id}: " . $e->getMessage());
                            }
                        }
                    }
                } else {
                    // Log paid sales order (stock NOT adjusted)
                    Log::info('Skipping stock adjustment for paid sale', [
                        'sale_id' => $salesOrder->id,
                        'invoice' => $salesOrder->invoice,
                        'status' => $salesOrder->status,
                        'reason' => 'Invoice already paid - stock adjustment not needed',
                    ]);
                }

                // Delete the sales order
                $salesOrder->delete();
                Log::info("Successfully deleted sales order {$id}");
            });

            Log::info('Single delete completed successfully', [
                'sales_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.sales')->with('success', 'Sales order deleted successfully');
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Delete sales order failed', [
                'sales_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.sales')->with('error', 'Error deleting sales order. Please try again.');
        }
    }
}