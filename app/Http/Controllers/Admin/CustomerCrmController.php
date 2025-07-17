<?php

namespace App\Http\Controllers\Admin;

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
        $data = $this->crmService->getCustomerCrmData($id, $request->input('page', 1));
        return response()->json($data);
    }

    public function storeInteraction(Request $request, $customerId)
    {
        $request->validate([
            'type' => 'required|string',
            'notes' => 'required|string',
            'interaction_date' => 'required|date',
        ]);

        $interaction = $this->crmService->storeCustomerInteraction($request->all(), $customerId);

        return response()->json($interaction);
    }

    public function getProductHistory(Request $request, $id)
    {
        $productHistory = $this->crmService->getCustomerProductHistory($id);

        return response()->json([
            'product_history' => $productHistory,
        ]);
    }
}
