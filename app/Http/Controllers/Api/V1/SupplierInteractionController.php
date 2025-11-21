<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierInteractionResource;
use App\Models\SupplierInteraction;
use Illuminate\Http\Request;

/**
 * @group Supplier Interactions
 *
 * APIs for managing supplier interactions
 */
class SupplierInteractionController extends Controller
{
    /**
     * Display a listing of the supplier interactions.
     *
     * @queryParam per_page int The number of interactions to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $interactions = SupplierInteraction::with('supplier')->paginate($perPage);
        return SupplierInteractionResource::collection($interactions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam type string required The type of interaction (e.g., Call, Email, Meeting). Example: Email
     * @bodyParam notes string Notes about the interaction. Example: Sent follow-up email.
     * @bodyParam interaction_date date required The date of the interaction. Example: 2023-10-26
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "supplier_id": 1,
     *         "user_id": 1,
     *         "type": "Email",
     *         "notes": "Sent follow-up email.",
     *         "interaction_date": "2023-10-26",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'interaction_date' => 'required|date',
        ]);

        $supplier_interaction = SupplierInteraction::create($validated);

        return new SupplierInteractionResource($supplier_interaction);
    }

    /**
     * Display the specified supplier interaction.
     *
     * @urlParam supplier_interaction required The ID of the supplier interaction. Example: 1
     */
    public function show(SupplierInteraction $supplier_interaction)
    {
        return new SupplierInteractionResource($supplier_interaction->load('supplier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam supplier_interaction integer required The ID of the supplier interaction. Example: 1
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam type string required The type of interaction (e.g., Call, Email, Meeting). Example: Call
     * @bodyParam notes string Notes about the interaction. Example: Discussed new product line.
     * @bodyParam interaction_date date required The date of the interaction. Example: 2023-10-27
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "supplier_id": 1,
     *         "user_id": 1,
     *         "type": "Call",
     *         "notes": "Discussed new product line.",
     *         "interaction_date": "2023-10-27",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, SupplierInteraction $supplier_interaction)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'interaction_date' => 'required|date',
        ]);

        $supplier_interaction->update($validated);

        return new SupplierInteractionResource($supplier_interaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam supplier_interaction integer required The ID of the supplier interaction to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(SupplierInteraction $supplier_interaction)
    {
        $supplier_interaction->delete();

        return response()->noContent();
    }
}
