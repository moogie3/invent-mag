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
        $filters = $request->only(['month', 'year']);
        $data = $this->purchaseService->getPurchaseIndexData($filters, $entries);

        return view('admin.po.index', $data);
    }

    public function create()
    {
        $data = $this->purchaseService->getPurchaseCreateData();

        return view('admin.po.purchase-create', $data);
    }

    public function edit($id)
    {
        $data = $this->purchaseService->getPurchaseEditData($id);

        return view('admin.po.purchase-edit', $data);
    }

    public function view($id)
    {
        $data = $this->purchaseService->getPurchaseViewData($id);

        return view('admin.po.purchase-view', $data);
    }

    public function modalView($id)
    {
        try {
            $pos = $this->purchaseService->getPurchaseForModal($id);
            return view('admin.layouts.modals.pomodals-view', compact('pos'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Purchase record not found for modal view: {$id}");
            return response('<div class="alert alert-danger">Purchase record not found.</div>', 404);
        } catch (\Exception $e) {
            Log::error("Error loading purchase modal view for ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response('<div class="alert alert-danger">Error loading purchase details: ' . $e->getMessage() . '</div>', 500);
        }
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
            return redirect()->route('admin.po')->with('success', 'Purchase Order created successfully.');
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

    public function getPurchaseMetrics()
    {
        $metrics = $this->purchaseService->getPurchaseMetrics();

        return response()->json($metrics);
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