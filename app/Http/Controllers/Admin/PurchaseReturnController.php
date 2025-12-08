<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
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
        return view('admin.purchase-returns.index', $data);
    }

    public function create()
    {
        $purchases = Purchase::all();
        return view('admin.purchase-returns.create', compact('purchases'));
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

        return redirect()->route('admin.purchase-returns.index')->with('success', 'Purchase return created successfully.');
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        return view('admin.purchase-returns.show', compact('purchaseReturn'));
    }

    public function edit(PurchaseReturn $purchaseReturn)
    {
        $purchases = Purchase::all();
        return view('admin.purchase-returns.edit', compact('purchaseReturn', 'purchases'));
    }

    public function update(Request $request, PurchaseReturn $purchaseReturn)
    {
        //
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        //
    }

    public function getPurchaseItems(Purchase $purchase)
    {
        return response()->json($purchase->items()->with('product')->get());
    }
}
