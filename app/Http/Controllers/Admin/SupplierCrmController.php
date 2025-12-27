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

        try {
            $interaction = $this->crmService->storeSupplierInteraction($request->all(), $supplierId);
            return response()->json($interaction);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store interaction: ' . $e->getMessage()], 500);
        }
    }

    public function getHistoricalPurchases(Request $request, $id)
    {
        try {
            $historicalPurchases = $this->crmService->getSupplierHistoricalPurchases($id);

            return response()->json([
                'historical_purchases' => $historicalPurchases,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load historical purchases: ' . $e->getMessage()], 500);
        }
    }

    public function getProductHistory(Request $request, $id)
    {
        try {
            $productHistory = $this->crmService->getSupplierProductHistory($id);

            return response()->json([
                'product_history' => $productHistory,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load product history: ' . $e->getMessage()], 500);
        }
    }
}
