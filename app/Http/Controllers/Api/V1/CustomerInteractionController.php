<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerInteractionResource;
use App\Models\CustomerInteraction;
use App\Services\CrmService;
use Illuminate\Http\Request;

/**
 * @group Customer Interactions
 *
 * APIs for managing customer interactions
 */
class CustomerInteractionController extends Controller
{
    protected $crmService;

    public function __construct(\App\Services\CrmService $crmService)
    {
        $this->crmService = $crmService;
    }
    /**
     * Display a listing of the customer interactions.
     *
     * @group Customer Interactions
     * @authenticated
     * @queryParam per_page int The number of interactions to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of customer interactions.
     * @responseField data[].id integer The ID of the interaction.
     * @responseField data[].customer_id integer The ID of the customer.
     * @responseField data[].user_id integer The ID of the user.
     * @responseField data[].type string The type of interaction.
     * @responseField data[].notes string The notes for the interaction.
     * @responseField data[].interaction_date string The date of the interaction.
     * @responseField data[].created_at string The date and time the interaction was created.
     * @responseField data[].updated_at string The date and time the interaction was last updated.
     * @responseField data[].customer object The customer associated with the interaction.
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
        $interactions = CustomerInteraction::with('customer')->paginate($perPage);
        return CustomerInteractionResource::collection($interactions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Customer Interactions
     * @authenticated
     * @bodyParam customer_id integer required The ID of the customer. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam type string required The type of interaction. Example: "call"
     * @bodyParam notes string The notes for the interaction. Example: "Followed up on the recent order."
     * @bodyParam interaction_date date required The date of the interaction. Example: "2025-11-28"
     *
     * @responseField id integer The ID of the interaction.
     * @responseField customer_id integer The ID of the customer.
     * @responseField user_id integer The ID of the user.
     * @responseField type string The type of interaction.
     * @responseField notes string The notes for the interaction.
     * @responseField interaction_date string The date of the interaction.
     * @responseField created_at string The date and time the interaction was created.
     * @responseField updated_at string The date and time the interaction was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreCustomerInteractionRequest $request)
    {
        $validated = $request->validated();
        $interaction = $this->crmService->storeCustomerInteraction($validated, $validated['customer_id']);
        return new CustomerInteractionResource($interaction);
    }

    /**
     * Display the specified customer interaction.
     *
     * @group Customer Interactions
     * @authenticated
     * @urlParam customer_interaction required The ID of the customer interaction. Example: 1
     *
     * @responseField id integer The ID of the interaction.
     * @responseField customer_id integer The ID of the customer.
     * @responseField user_id integer The ID of the user.
     * @responseField type string The type of interaction.
     * @responseField notes string The notes for the interaction.
     * @responseField interaction_date string The date of the interaction.
     * @responseField created_at string The date and time the interaction was created.
     * @responseField updated_at string The date and time the interaction was last updated.
     * @responseField customer object The customer associated with the interaction.
     */
    public function show(CustomerInteraction $customer_interaction)
    {
        return new CustomerInteractionResource($customer_interaction->load('customer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Customer Interactions
     * @authenticated
     * @urlParam customer_interaction required The ID of the customer interaction. Example: 1
     * @bodyParam customer_id integer required The ID of the customer. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam type string required The type of interaction. Example: "call"
     * @bodyParam notes string The notes for the interaction. Example: "Followed up on the recent order."
     * @bodyParam interaction_date date required The date of the interaction. Example: "2025-11-28"
     *
     * @responseField id integer The ID of the interaction.
     * @responseField customer_id integer The ID of the customer.
     * @responseField user_id integer The ID of the user.
     * @responseField type string The type of interaction.
     * @responseField notes string The notes for the interaction.
     * @responseField interaction_date string The date of the interaction.
     * @responseField created_at string The date and time the interaction was created.
     * @responseField updated_at string The date and time the interaction was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateCustomerInteractionRequest $request, CustomerInteraction $customer_interaction)
    {
        $validated = $request->validated();
        $customer_interaction->update($validated);

        return new CustomerInteractionResource($customer_interaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Customer Interactions
     * @authenticated
     * @urlParam customer_interaction required The ID of the customer interaction. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(CustomerInteraction $customer_interaction)
    {
        $customer_interaction->delete();

        return response()->noContent();
    }
}
