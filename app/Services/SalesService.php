<?php

namespace App\Services;

use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\SalesHelper;
use App\Models\Customer;

class SalesService
{
    public function getSalesIndexData(array $filters, int $entries)
    {
        $query = Sales::with(['product', 'customer', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('order_date', $filters['month']);
        }
        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('order_date', $filters['year']);
        }

        $sales = $query->paginate($entries);
        $totalinvoice = $query->count();
        $unpaidDebt = Sales::all()->where('status', 'Unpaid')->sum('total');
        $totalMonthly = Sales::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $pendingOrders = Sales::where('status', 'Unpaid')->count();
        $dueInvoices = Sales::where('status', 'Unpaid')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->count();
        $posTotal = Sales::where('is_pos', true)->sum('total');
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return compact('posTotal', 'dueInvoices', 'entries', 'sales', 'totalinvoice', 'shopname', 'address', 'unpaidDebt', 'pendingOrders', 'totalMonthly');
    }

    public function getSalesCreateData()
    {
        $sales = Sales::all();
        $customers = Customer::all();
        $products = Product::all();
        $items = SalesItem::all();
        $tax = Tax::where('is_active', 1)->first();

        return compact('sales', 'customers', 'products', 'items', 'tax');
    }

    public function getSalesEditData($id)
    {
        $sales = Sales::with(['salesItems', 'customer'])->find($id);
        $customers = Customer::all();
        $tax = Tax::where('is_active', 1)->first();
        $isPaid = $sales->status == 'Paid';

        return compact('sales', 'customers', 'tax', 'isPaid');
    }

    public function getSalesViewData($id)
    {
        $sales = Sales::with(['salesItems', 'customer'])->find($id);

        if (strpos($sales->invoice, 'POS-') === 0) {
            return redirect()->route('admin.pos.receipt', $id);
        }

        $customer = Customer::all();
        $tax = Tax::first();

        $itemCount = $sales->salesItems->count();
        $subtotal = 0;
        $totalItemDiscount = 0;

        foreach ($sales->salesItems as $item) {
            $itemSubtotal = $item->customer_price * $item->quantity;
            if ($item->discount_type === 'percentage') {
                $itemDiscountAmount = ($itemSubtotal * $item->discount) / 100;
            } else {
                $itemDiscountAmount = $item->discount * $item->quantity;
            }
            $totalItemDiscount += $itemDiscountAmount;
            $subtotal += ($itemSubtotal - $itemDiscountAmount);
        }

        $orderDiscount = 0;
        if ($sales->order_discount > 0) {
            if ($sales->order_discount_type === 'percentage') {
                $orderDiscount = ($subtotal * $sales->order_discount) / 100;
            } else {
                $orderDiscount = $sales->order_discount;
            }
        }

        $taxAmount = $sales->total_tax;
        $finalTotal = $sales->total;

        $summary = [
            'itemCount' => $itemCount,
            'subtotal' => $subtotal,
            'totalItemDiscount' => $totalItemDiscount,
            'orderDiscount' => $orderDiscount,
            'taxAmount' => $taxAmount,
            'finalTotal' => $finalTotal
        ];

        return compact(
            'sales',
            'customer',
            'tax',
            'summary',
            'itemCount',
            'subtotal',
            'orderDiscount',
            'finalTotal',
            'totalItemDiscount',
            'taxAmount'
        );
    }

    public function getSalesMetrics()
    {
        $totalinvoice = Sales::count();
        $unpaidDebt = Sales::all()->where('status', 'Unpaid')->sum('total');
        $totalMonthly = Sales::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $pendingOrders = Sales::where('status', 'Unpaid')->count();
        $dueInvoices = Sales::where('status', 'Unpaid')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->count();
        $posTotal = Sales::where('is_pos', true)->sum('total');

        return [
            'totalinvoice' => $totalinvoice,
            'unpaidDebt' => $unpaidDebt,
            'totalMonthly' => $totalMonthly,
            'pendingOrders' => $pendingOrders,
            'dueInvoices' => $dueInvoices,
            'posTotal' => $posTotal,
        ];
    }

    public function createSale(array $data): Sales
    {
        return DB::transaction(function () use ($data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            $subTotal = 0;
            foreach ($products as $product) {
                $subTotal += $product['customer_price'] * $product['quantity'];
            }

            $orderDiscount = $data['discount_total'] ?? 0;
            $orderDiscountType = $data['discount_total_type'] ?? 'fixed';
            $orderDiscountAmount = SalesHelper::calculateDiscount($subTotal, $orderDiscount, $orderDiscountType);

            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;
            $taxAmount = SalesHelper::calculateTaxAmount($subTotal - $orderDiscountAmount, $taxRate);

            $grandTotal = ($subTotal - $orderDiscountAmount) + $taxAmount;

            $invoice = $data['invoice'] ?? $this->generateInvoiceNumber();

            $sale = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'order_discount' => $orderDiscountAmount,
                'order_discount_type' => $orderDiscountType,
                'status' => 'Unpaid',
                'payment_type' => '-',
                'is_pos' => false,
            ]);

            foreach ($products as $productData) {
                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'customer_price' => $productData['customer_price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed'),
                ]);

                $product = Product::find($productData['product_id']);
                if ($product) {
                    $product->decrement('stock_quantity', $productData['quantity']);
                }
            }

            return $sale;
        });
    }

    public function updateSale(Sales $sale, array $data): Sales
    {
        return DB::transaction(function () use ($sale, $data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            // Revert old stock quantities
            foreach ($sale->salesItems as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $oldItem->quantity);
                }
            }

            $sale->salesItems()->delete();

            $subTotal = 0;
            foreach ($products as $productData) {
                $subTotal += $productData['customer_price'] * $productData['quantity'];
            }

            $orderDiscount = $data['discount_total'] ?? 0;
            $orderDiscountType = $data['discount_total_type'] ?? 'fixed';
            $orderDiscountAmount = SalesHelper::calculateDiscount($subTotal, $orderDiscount, $orderDiscountType);

            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;
            $taxAmount = SalesHelper::calculateTaxAmount($subTotal - $orderDiscountAmount, $taxRate);

            $grandTotal = ($subTotal - $orderDiscountAmount) + $taxAmount;

            $sale->update([
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'order_discount' => $orderDiscountAmount,
                'order_discount_type' => $orderDiscountType,
            ]);

            foreach ($products as $productData) {
                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'customer_price' => $productData['customer_price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed'),
                ]);

                $product = Product::find($productData['product_id']);
                if ($product) {
                    $product->decrement('stock_quantity', $productData['quantity']);
                }
            }

            return $sale;
        });
    }

    public function deleteSale(Sales $sale): void
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->salesItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }
            $sale->delete();
        });
    }

    public function bulkDeleteSales(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            $sales = Sales::whereIn('id', $ids)->with('salesItems')->get();
            foreach ($sales as $sale) {
                foreach ($sale->salesItems as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $item->quantity);
                    }
                }
                $sale->delete();
            }
        });
    }

    public function bulkMarkPaid(array $ids): int
    {
        return Sales::whereIn('id', $ids)->update([
            'status' => 'Paid',
            'payment_date' => now(),
            'amount_received' => DB::raw('total'),
            'change_amount' => 0,
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $lastSalesInvoice = Sales::latest()->first();
        $invoiceNumber = 1;
        if ($lastSalesInvoice) {
            $lastNumber = (int) substr($lastSalesInvoice->invoice, 4);
            $invoiceNumber = $lastNumber + 1;
        }
        return 'INV-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
    }
}