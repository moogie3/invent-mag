<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CrmService;
use Illuminate\Http\Request;

class SupplierCrmController extends Controller
{
    protected $crmService;

    public function __construct(CrmService $crmService)
    {
        $this->crmService = $crmService;
    }

    public function show(Request $request, $id)
    {
        try {
            $data = $this->crmService->getSupplierCrmData($id, $request->input('page', 1));
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load SRM data: ' . $e->getMessage()], 500);
        }
    }

    public function storeInteraction(Request $request, $supplierId)
    {
        $request->validate([
            'type' => 'required|string',
            'notes' => 'required|string',
            'interaction_date' => 'required|date',
        ]);

        $interaction = $this->crmService->storeSupplierInteraction($request->all(), $supplierId);

        return response()->json($interaction);
    }

    public function getHistoricalPurchases(Request $request, $id)
    {
        $supplier = \App\Models\Supplier::findOrFail($id);

        $historicalPurchases = $supplier->purchases()
            ->with('items.product')
            ->orderByDesc('order_date')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'invoice' => $purchase->invoice,
                    'order_date' => $purchase->order_date,
                    'due_date' => $purchase->due_date,
                    'payment_method' => $purchase->payment_type,
                    'status' => $purchase->status,
                    'total_amount' => $purchase->grand_total,
                    'discount_amount' => $purchase->discount_total,
                    'purchase_items' => $purchase->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => $item->product,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                        ];
                    }),
                ];
            });

        return response()->json([
            'historical_purchases' => $historicalPurchases,
        ]);
    }

    public function getProductHistory(Request $request, $id)
    {
        $productHistory = $this->crmService->getSupplierProductHistory($id);

        return response()->json([
            'product_history' => $productHistory,
        ]);
    }
}
