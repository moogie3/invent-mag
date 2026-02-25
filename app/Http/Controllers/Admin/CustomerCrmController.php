<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Http\Controllers\Controller;
use App\Services\CrmService;
use Illuminate\Http\Request;

class CustomerCrmController extends Controller
{
    protected $crmService;

    public function __construct(CrmService $crmService)
    {
        $this->crmService = $crmService;
    }

    public function show(Request $request, $id)
    {
        try {
            $data = $this->crmService->getCustomerCrmData($id, $request->input('page', 1));
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load CRM data: ' . $e->getMessage()], 500);
        }
    }

    public function storeInteraction(Request $request, $customerId)
    {
        $request->validate([
            'type' => 'required|string',
            'notes' => 'required|string',
            'interaction_date' => 'required|date',
        ]);

        try {
            $interaction = $this->crmService->storeCustomerInteraction($request->all(), $customerId);
            return response()->json($interaction);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store interaction: ' . $e->getMessage()], 500);
        }
    }

    public function getProductHistory(Request $request, $id)
    {
        try {
            $productHistory = $this->crmService->getCustomerProductHistory($id);
            return response()->json($productHistory);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load product history: ' . $e->getMessage()], 500);
        }
    }

    public function getHistoricalPurchases(Customer $customer)
    {
        try {
            $historicalPurchases = $this->crmService->getHistoricalPurchases($customer);
            return response()->json([
                'success' => true,
                'historical_purchases' => $historicalPurchases,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load historical purchases: ' . $e->getMessage()], 500);
        }
    }
}
