<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierInteractionResource;
use App\Models\SupplierInteraction;
use App\Services\CrmService;
use Illuminate\Http\Request;

/**
 * @group Supplier Interactions
 *
 * APIs for managing supplier interactions
 */
class SupplierInteractionController extends Controller
{
    protected $crmService;

    public function __construct(\App\Services\CrmService $crmService)
    {
        $this->crmService = $crmService;
    }
    /**
     * Display a listing of the supplier interactions.
     *
     * @group Supplier Interactions
     * @authenticated
     * @queryParam per_page int The number of interactions to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of supplier interactions.
     * @responseField data[].id integer The ID of the interaction.
     * @responseField data[].supplier_id integer The ID of the supplier.
     * @responseField data[].user_id integer The ID of the user.
     * @responseField data[].type string The type of interaction.
     * @responseField data[].notes string The notes for the interaction.
     * @responseField data[].interaction_date string The date of the interaction.
     * @responseField data[].created_at string The date and time the interaction was created.
     * @responseField data[].updated_at string The date and time the interaction was last updated.
     * @responseField data[].supplier object The supplier associated with the interaction.
     * @responseField links object Links for pagination.
     * @responseField links.first string The URL of the first page.
     * @responseField links.last string The URL of the last page.
     * @responseField links.prev string The URL of the previous page.
     * @responseField links.next string The URL of the next page.
     * @responseField meta object Metadata for pagination.
     * @responseField meta.current_page integer The current page number.
     * @responseField meta.from integer The starting number of the results on the current page.
     * @responseField meta.last_page integer The last page number.
     * @responseField meta.path string The URL path.
     * @responseField meta.per_page integer The number of results per page.
     * @responseField meta.to integer The ending number of the results on the current page.
     * @responseField meta.total integer The total number of results.
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
     * @group Supplier Interactions
     * @authenticated
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam type string required The type of interaction (e.g., Call, Email, Meeting). Example: Email
     * @bodyParam notes string Notes about the interaction. Example: Sent follow-up email.
     * @bodyParam interaction_date date required The date of the interaction. Example: 2023-10-26
     *
     * @responseField id integer The ID of the interaction.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField user_id integer The ID of the user.
     * @responseField type string The type of interaction.
     * @responseField notes string The notes for the interaction.
     * @responseField interaction_date string The date of the interaction.
     * @responseField created_at string The date and time the interaction was created.
     * @responseField updated_at string The date and time the interaction was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreSupplierInteractionRequest $request)
    {
        $validated = $request->validated();
        $interaction = $this->crmService->storeSupplierInteraction($validated, $validated['supplier_id']);
        return new SupplierInteractionResource($interaction);
    }

    /**
     * Display the specified supplier interaction.
     *
     * @group Supplier Interactions
     * @authenticated
     * @urlParam supplier_interaction required The ID of the supplier interaction. Example: 1
     *
     * @responseField id integer The ID of the interaction.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField user_id integer The ID of the user.
     * @responseField type string The type of interaction.
     * @responseField notes string The notes for the interaction.
     * @responseField interaction_date string The date of the interaction.
     * @responseField created_at string The date and time the interaction was created.
     * @responseField updated_at string The date and time the interaction was last updated.
     * @responseField supplier object The supplier associated with the interaction.
     */
    public function show(SupplierInteraction $supplier_interaction)
    {
        return new SupplierInteractionResource($supplier_interaction->load('supplier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Supplier Interactions
     * @authenticated
     * @urlParam supplier_interaction integer required The ID of the supplier interaction. Example: 1
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam type string required The type of interaction (e.g., Call, Email, Meeting). Example: Call
     * @bodyParam notes string Notes about the interaction. Example: Discussed new product line.
     * @bodyParam interaction_date date required The date of the interaction. Example: 2023-10-27
     *
     * @responseField id integer The ID of the interaction.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField user_id integer The ID of the user.
     * @responseField type string The type of interaction.
     * @responseField notes string The notes for the interaction.
     * @responseField interaction_date string The date of the interaction.
     * @responseField created_at string The date and time the interaction was created.
     * @responseField updated_at string The date and time the interaction was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateSupplierInteractionRequest $request, SupplierInteraction $supplier_interaction)
    {
        $validated = $request->validated();
        $supplier_interaction->update($validated);

        return new SupplierInteractionResource($supplier_interaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Supplier Interactions
     * @authenticated
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
