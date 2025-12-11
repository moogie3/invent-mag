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
        $purchases = Purchase::all();
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

    public function show(PurchaseReturn $purchaseReturn)
    {
        return view('admin.por.show', compact('purchaseReturn'));
    }

    public function edit(PurchaseReturn $purchaseReturn)
    {
        $purchases = Purchase::all();
        return view('admin.por.edit', compact('purchaseReturn', 'purchases'));
    }

    public function update(Request $request, PurchaseReturn $purchaseReturn)
    {
        $request->validate([
            'purchase_id' => 'required|exists:po,id',
            'return_date' => 'required|date',
            'items' => 'required|json',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $this->purchaseReturnService->updatePurchaseReturn($purchaseReturn, $request->all());

        return redirect()->route('admin.por.index')->with('success', 'Purchase return updated successfully.');
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->delete();
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

    public function modalView(PurchaseReturn $purchaseReturn)
    {
        $statusClass = PurchaseReturnHelper::getStatusClass($purchaseReturn->status);
        $statusText = PurchaseReturnHelper::getStatusText($purchaseReturn->status);

        return view('admin.layouts.modals.po.pormodals-view', compact('purchaseReturn', 'statusClass', 'statusText'));
    }
}