<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\POItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\PurchaseHelper;
use App\Helpers\CurrencyHelper;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;

class PurchaseService
{
    public function getPurchaseIndexData(array $filters, int $entries)
    {
        $query = Purchase::with(['items', 'supplier', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('order_date', $filters['month']);
            if (!isset($filters['year'])) { // If year is not specified, default to current year
                $query->whereYear('order_date', Carbon::now()->year);
            }
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('order_date', $filters['year']);
        }

        $pos = $query->paginate($entries);



        $totalinvoice = $pos->total();
        $items = POItem::all();

        $inCount = 0;
        $inCountamount = 0;
        $outCount = 0;
        $outCountamount = 0;
        $totalMonthly = 0;
        $paymentMonthly = 0;

        $allPurchases = Purchase::with('items', 'supplier')->get();
        foreach ($allPurchases as $p) {

            if ($p->supplier->location === 'IN') {
                $inCount++;
                if ($p->status === 'Unpaid') {
                    $inCountamount += $p->total_amount;
                }
            }

            if ($p->supplier->location === 'OUT') {
                $outCount++;
                if ($p->status === 'Unpaid') {
                    $outCountamount += $p->total_amount;
                }
            }

            if ($p->order_date->isCurrentMonth()) {
                $totalMonthly += $p->total_amount;
            }

            if ($p->status === 'Paid' && $p->order_date->isCurrentMonth()) {
                $paymentMonthly += $p->total_amount;
            }
        }

        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return compact('inCountamount', 'outCountamount', 'pos', 'inCount', 'outCount', 'shopname', 'address', 'entries', 'totalinvoice', 'totalMonthly', 'paymentMonthly', 'items');
    }

    public function getPurchaseCreateData()
    {
        $pos = Purchase::all();
        $suppliers = Supplier::all();
        $products = Product::all();

        return compact('pos', 'suppliers', 'products');
    }

    public function getPurchaseEditData($id)
    {
        $pos = Purchase::with(['items', 'supplier'])->find($id);

        if (!$pos) {
            return []; // Or throw an exception, depending on desired behavior
        }

        $suppliers = Supplier::all();
        $products = Product::all();
        $items = POItem::all();
        $isPaid = $pos->status == 'Paid';

        return compact('pos', 'suppliers', 'products', 'items', 'isPaid');
    }

    public function getPurchaseViewData($id)
    {
        $pos = Purchase::with(['items', 'supplier', 'payments'])->find($id);

        if (!$pos) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Purchase with ID {$id} not found.");
        }

        $suppliers = Supplier::all();
        $items = POItem::all();
        $summary = PurchaseHelper::calculateInvoiceSummary($pos->items->toArray(), $pos->discount_total, $pos->discount_total_type);
        $subtotal = $summary['subtotal'];
        $itemCount = $summary['itemCount'];
        $totalProductDiscount = $summary['totalProductDiscount'];
        $orderDiscount = $summary['orderDiscount'];
        $finalTotal = $summary['finalTotal'];

        return compact('pos', 'suppliers', 'items', 'itemCount', 'subtotal', 'orderDiscount', 'finalTotal', 'totalProductDiscount');
    }

    public function getPurchaseForModal($id)
    {
        return Purchase::with(['supplier', 'items.product', 'payments'])->findOrFail($id);
    }

    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            /** @var \App\Models\Purchase $purchase */
            $purchase = Purchase::create([
                'invoice' => $data['invoice'],
                'supplier_id' => $data['supplier_id'],
                'user_id' => Auth::id(),
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_total_type' => $data['discount_total_type'] ?? 'fixed',
                'status' => 'Unpaid',
                'total' => 0, // Will be calculated after items are added
            ]);

            $totalAmount = 0;
            $subtotal = 0;
            foreach ($products as $productData) {
                $subtotal += $productData['price'] * $productData['quantity'];
                $itemTotal = PurchaseHelper::calculateTotal($productData['price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed');
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'remaining_quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => $itemTotal,
                    'expiry_date' => $productData['expiry_date'] ?? null,
                ]);
                $totalAmount += $itemTotal;

                $product = Product::find($productData['product_id']);
                if ($product) {
                    $product->increment('stock_quantity', $productData['quantity']);
                }
            }

            $orderDiscount = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $finalTotal = $totalAmount - $orderDiscount;
            $purchase->update(['total' => $finalTotal]);

            return $purchase;
        });
    }

    public function updatePurchase(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            /** @var \App\Models\Purchase $purchase */
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            // Revert old stock quantities
            foreach ($purchase->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $oldItem->quantity);
                }
            }

            $purchase->items()->delete();

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_total_type' => $data['discount_total_type'] ?? 'fixed',
                'status' => $data['status'] ?? 'Unpaid',
                'payment_type' => $data['payment_type'] ?? '-',
            ]);

            $totalAmount = 0;
            $subtotal = 0;
            foreach ($products as $productData) {
                $subtotal += $productData['price'] * $productData['quantity'];
                $itemTotal = PurchaseHelper::calculateTotal($productData['price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed');
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'remaining_quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => $itemTotal,
                    'expiry_date' => $productData['expiry_date'] ?? null,
                ]);
                $totalAmount += $itemTotal;

                $product = Product::find($productData['product_id']);
                if ($product) {
                    $product->increment('stock_quantity', $productData['quantity']);
                }
            }

            $orderDiscount = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $finalTotal = $totalAmount - $orderDiscount;
            $purchase->update(['total' => $finalTotal]);

            return $purchase;
        });
    }

    public function addPayment(Purchase $purchase, array $data): \App\Models\Payment
    {
        return DB::transaction(function () use ($purchase, $data) {
            $payment = $purchase->payments()->create([
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            $purchase->load('payments'); // Refresh the payments relationship
            $this->updatePurchaseStatus($purchase);

            return $payment;
        });
    }

    public function updatePurchaseStatus(Purchase $purchase)
    {
        $totalPaid = $purchase->total_paid;
        $grandTotal = $purchase->grand_total;

        if ($totalPaid >= $grandTotal) {
            $purchase->status = 'Paid';
        } elseif ($totalPaid > 0 && $totalPaid < $grandTotal) {
            $purchase->status = 'Partial';
        } else {
            $purchase->status = 'Unpaid';
        }

        $purchase->save();
    }

    public function deletePurchase(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
                // Delete the POItem as well
                $item->delete();
            }
            $purchase->delete();
        });
    }

    public function bulkDeletePurchases(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            $purchases = Purchase::whereIn('id', $ids)->with('items')->get();
            foreach ($purchases as $purchase) {
                foreach ($purchase->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('stock_quantity', $item->quantity);
                    }
                    // Delete the POItem as well
                    $item->delete();
                }
                $purchase->delete();
            }
        });
    }

    public function bulkMarkPaid(array $ids)
    {
        $updatedCount = 0;
        DB::transaction(function () use ($ids, &$updatedCount) {
            $purchases = Purchase::whereIn('id', $ids)->with('payments', 'items')->get(); // Added with('items') // Added with('payments')
            foreach ($purchases as $purchase) {
                if ($purchase->balance > 0) {
                    $this->addPayment($purchase, [
                        'amount' => $purchase->balance,
                        'payment_date' => now(),
                        'payment_method' => 'Unknown',
                        'notes' => 'Bulk marked as paid.',
                    ]);
                }
                $purchase->refresh(); // Refresh the purchase model to get the latest status
                $updatedCount++;
            }
        });
        return $updatedCount;
    }

    public function getPurchaseMetrics()
    {
        $totalinvoice = Purchase::count();
        $inCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->count();
        $inCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        $outCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->count();
        $outCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        $totalMonthly = Purchase::whereMonth('order_date', now()->month)->whereYear('order_date', now()->year)->sum('total');
        $paymentMonthly = Purchase::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->where('status', 'Paid')->sum('total');

        return [
            'totalinvoice' => $totalinvoice,
            'inCount' => $inCount,
            'inCountamount' => $inCountamount,
            'outCount' => $outCount,
            'outCountamount' => $outCountamount,
            'totalMonthly' => $totalMonthly,
            'paymentMonthly' => $paymentMonthly,
        ];
    }

    public function getExpiringPurchaseCount(): int
    {
        return Purchase::where('due_date', '<=', Carbon::now()->addDays(90))
                        ->where('status', '!=', 'Paid')
                        ->count();
    }

    public function getExpiringPurchases()
    {
        $expiringPurchases = Purchase::with('supplier')
            ->where('due_date', '<=', Carbon::now()->addDays(90))
            ->where('status', '!=', 'Paid')
            ->orderBy('due_date', 'asc')
            ->get();

        return $expiringPurchases->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'invoice' => $purchase->invoice,
                'supplier' => $purchase->supplier,
                'due_date' => Carbon::parse($purchase->due_date)->format('d M Y'),
                'total' => CurrencyHelper::format($purchase->total),
            ];
        });
    }
}