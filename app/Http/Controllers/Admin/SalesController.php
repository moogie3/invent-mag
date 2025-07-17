<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tax;
use App\Models\User;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $query = Sales::with(['product', 'customer', 'user']);

        if ($request->has('month') && $request->month) {
            $query->whereMonth('order_date', $request->month);
        }
        if ($request->has('year') && $request->year) {
            $query->whereYear('order_date', $request->year);
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

        return view('admin.sales.index', compact('posTotal', 'dueInvoices', 'entries', 'sales', 'totalinvoice', 'shopname', 'address', 'unpaidDebt', 'pendingOrders', 'totalMonthly'));
    }

    public function create()
    {
        $sales = Sales::all();
        $customers = Customer::all();
        $products = Product::all();
        $items = SalesItem::all();
        $tax = Tax::where('is_active', 1)->first();

        return view('admin.sales.sales-create', compact('sales', 'customers', 'products', 'items', 'tax'));
    }

    public function edit($id)
    {
        $sales = Sales::with(['salesItems', 'customer'])->find($id);
        $customers = Customer::all();
        $tax = Tax::where('is_active', 1)->first();
        $isPaid = $sales->status == 'Paid';

        return view('admin.sales.sales-edit', compact('sales', 'customers', 'tax', 'isPaid'));
    }

    public function view($id)
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

        return view('admin.sales.sales-view', compact(
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
        ));
    }

    public function modalViews($id)
    {
        try {
            $sales = Sales::with(['customer', 'salesItems.product'])->findOrFail($id);
            return view('admin.layouts.modals.salesmodals-view', compact('sales'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Sales record not found for modal view: {$id}");
            return response('<div class="alert alert-danger">Sales record not found.</div>', 404);
        } catch (\Exception $e) {
            Log::error("Error loading sales modal view for ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response('<div class="alert alert-danger">Error loading sales details: ' . $e->getMessage() . '</div>', 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice' => 'nullable|string|unique:sales,invoice',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'products' => 'required|json',
            'discount_total' => 'nullable|numeric|min:0',
            'discount_total_type' => 'nullable|in:fixed,percentage',
        ]);

        try {
            $this->salesService->createSale($request->all());
            return redirect()->route('admin.sales')->with('success', 'Sale created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $sale = Sales::findOrFail($id);

        try {
            $this->salesService->updateSale($sale, $request->all());
            return redirect()->route('admin.sales.view', $id)->with('success', 'Sale updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function getCustomerPrice(Customer $customer, Product $product)
    {
        $latestSale = Sales::where('customer_id', $customer->id)
            ->whereHas('salesItems', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->latest()
            ->first();

        $pastPrice = 0;

        if ($latestSale) {
            $saleItem = $latestSale->salesItems()->where('product_id', $product->id)->first();
            if ($saleItem) {
                $pastPrice = floor($saleItem->customer_price);
            }
        }

        return response()->json(['past_price' => $pastPrice]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:sales,id',
        ]);

        try {
            $this->salesService->bulkDeleteSales($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted sales order(s)",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting sales orders. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function bulkMarkPaid(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sales,id',
        ]);

        try {
            $updatedCount = $this->salesService->bulkMarkPaid($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} sales order(s) as paid.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating sales orders.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $sale = Sales::findOrFail($id);

        try {
            $this->salesService->deleteSale($sale);
            return redirect()->route('admin.sales')->with('success', 'Sales order deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.sales')->with('error', 'Error deleting sales order. Please try again.');
        }
    }
}
