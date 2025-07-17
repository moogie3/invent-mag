<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $query = Purchase::with(['items', 'supplier', 'user']);

        if ($request->has('month') && $request->month) {
            $query->whereMonth('order_date', $request->month);
        }

        if ($request->has('year') && $request->year) {
            $query->whereYear('order_date', $request->year);
        }
        $pos = $query->paginate($entries);
        $totalinvoice = Purchase::count();
        $items = POItem::all();
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

        $totalMonthly = Purchase::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $paymentMonthly = Purchase::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->where('status', 'Paid')->sum('total');
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return view('admin.po.index', compact('inCountamount', 'outCountamount', 'pos', 'inCount', 'outCount', 'shopname', 'address', 'entries', 'totalinvoice', 'totalMonthly', 'paymentMonthly', 'items'));
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
        $isPaid = $pos->status == 'Paid';

        return view('admin.po.purchase-edit', compact('pos', 'suppliers', 'items', 'isPaid'));
    }

    public function view($id)
    {
        $pos = Purchase::with(['items', 'supplier'])->find($id);
        $suppliers = Supplier::all();
        $items = POItem::all();
        $summary = \App\Helpers\PurchaseHelper::calculateInvoiceSummary($pos->items, $pos->discount_total, $pos->discount_total_type);
        $subtotal = $summary['subtotal'];
        $itemCount = $summary['itemCount'];
        $totalProductDiscount = $summary['totalProductDiscount'];
        $orderDiscount = $summary['orderDiscount'];
        $finalTotal = $summary['finalTotal'];

        return view('admin.po.purchase-view', compact('pos', 'suppliers', 'items', 'itemCount', 'subtotal', 'orderDiscount', 'finalTotal', 'totalProductDiscount'));
    }

    public function modalView($id)
    {
        $pos = Purchase::with(['supplier', 'items.product'])->findOrFail($id);

        return view('admin.layouts.modals.pomodals-view', compact('pos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice' => 'required|string|unique:po,invoice',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'products' => 'required|json',
            'discount_total' => 'nullable|numeric',
            'discount_total_type' => 'nullable|in:fixed,percentage',
        ]);

        try {
            $this->purchaseService->createPurchase($request->all());
            return redirect()->route('admin.po.create')->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);

        try {
            $this->purchaseService->updatePurchase($purchase, $request->all());
            return redirect()->route('admin.po.view', $purchase->id)->with('success', 'Purchase order updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);

        try {
            $this->purchaseService->deletePurchase($purchase);
            return redirect()->route('admin.po')->with('success', 'Purchase order deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.po')->with('error', 'Error deleting purchase order. Please try again.');
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:po,id',
        ]);

        try {
            $this->purchaseService->bulkDeletePurchases($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted purchase order(s)",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting purchase orders. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function bulkMarkPaid(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:po,id',
        ]);

        try {
            $updatedCount = $this->purchaseService->bulkMarkPaid($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} purchase order(s) as paid.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating purchase orders.',
            ], 500);
        }
    }
}
