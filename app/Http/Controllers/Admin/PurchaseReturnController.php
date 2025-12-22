<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use App\Helpers\PurchaseReturnHelper;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    protected $purchaseReturnService;
    protected $purchaseService;

    public function __construct(PurchaseReturnService $purchaseReturnService, PurchaseService $purchaseService)
    {
        $this->purchaseReturnService = $purchaseReturnService;
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $filters = $request->only(['month', 'year']);
        $data = $this->purchaseReturnService->getPurchaseReturnIndexData($filters, $entries);
        return view('admin.por.index', $data);
    }

    public function create()
    {
        // Get purchases that do NOT have any associated purchase returns
        $purchases = Purchase::whereDoesntHave('purchaseReturns')->get();
        return view('admin.por.create', compact('purchases'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:po,id',
            'return_date' => 'required|date',
            'items' => 'required|json',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $this->purchaseService->createPurchaseReturn($request->all());

        return redirect()->route('admin.por.index')->with('success', 'Purchase return created successfully.');
    }

    public function show(PurchaseReturn $por)
    {
        $statusClass = PurchaseReturnHelper::getStatusClass($por->status);
        $statusText = PurchaseReturnHelper::getStatusText($por->status);

        return view('admin.por.show', compact('por', 'statusClass', 'statusText'));
    }

    public function edit(PurchaseReturn $por)
    {
        $purchases = Purchase::all();
        $isCompletedOrCanceled = in_array($por->status, ['Completed', 'Canceled']);
        return view('admin.por.edit', compact('por', 'purchases', 'isCompletedOrCanceled'));
    }

    public function update(Request $request, PurchaseReturn $por)
    {
        $request->validate([
            'purchase_id' => 'required|exists:po,id',
            'return_date' => 'required|date',
            'items' => 'required|json',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $this->purchaseReturnService->updatePurchaseReturn($por, $request->all());

        return redirect()->route('admin.por.index')->with('success', 'Purchase return updated successfully.');
    }

    public function destroy(PurchaseReturn $por)
    {
        $por->delete();
        return redirect()->route('admin.por.index')->with('success', 'Purchase return deleted successfully.');
    }
    public function getPurchaseItems(Purchase $purchase)
    {
        return response()->json($purchase->items()->with('product')->get());
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        PurchaseReturn::whereIn('id', $ids)->delete();
        return response()->json(['success' => true, 'message' => 'Selected purchase returns have been deleted.']);
    }

    public function bulkComplete(Request $request)
    {
        $ids = $request->input('ids');
        PurchaseReturn::whereIn('id', $ids)->update(['status' => 'Completed']);
        return response()->json(['success' => true, 'message' => 'Selected purchase returns have been marked as completed.']);
    }

    public function bulkCancel(Request $request)
    {
        $ids = $request->input('ids');
        PurchaseReturn::whereIn('id', $ids)->update(['status' => 'Canceled']);
        return response()->json(['success' => true, 'message' => 'Selected purchase returns have been marked as canceled.']);
    }

    public function modalView(PurchaseReturn $por)
    {
        $por->load(['purchase.supplier', 'items.product']); // Eager load relationships

        $statusClass = PurchaseReturnHelper::getStatusClass($por->status);
        $statusText = PurchaseReturnHelper::getStatusText($por->status);

        return view('admin.layouts.modals.po.pormodals-view', compact('por', 'statusClass', 'statusText'));
    }

    /**
     * @group Purchase Returns
     * @summary Bulk Export Purchase Returns
     * @bodyParam ids array required An array of purchase return IDs to export. Example: [1, 2, 3]
     * @bodyParam export_option string required The export format ('pdf' or 'csv'). Example: "csv"
     * @response 200 "The exported file."
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:purchase_returns,id',
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $file = $this->purchaseReturnService->bulkExportPurchaseReturns($request->ids, $request->export_option);
            return $file;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting purchase returns. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
