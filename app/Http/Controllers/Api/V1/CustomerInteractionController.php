<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerInteractionRequest;
use App\Http\Requests\Api\V1\UpdateCustomerInteractionRequest;
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
        $this->middleware('permission:view-customer-interactions')->only(['index', 'show']);
        $this->middleware('permission:create-customer-interactions')->only(['store']);
        $this->middleware('permission:edit-customer-interactions')->only(['update']);
        $this->middleware('permission:delete-customer-interactions')->only(['destroy']);
    }
    /**
     * Display a listing of the customer interactions.
     *
     * @group Customer Interactions
     * @authenticated
     * @queryParam per_page int The number of interactions to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"customer_id":1,"user_id":1,"type":"call",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 201 scenario="Success" {"data":{"id":1,"customer_id":1,"user_id":1,"type":"call",...}}
     * @response 422 scenario="Validation Error" {"message":"The customer_id field is required.","errors":{"customer_id":["The customer_id field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreCustomerInteractionRequest $request)
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
     * @response 200 scenario="Success" {"data":{"id":1,"customer_id":1,"user_id":1,"type":"call",...}}
     * @response 404 scenario="Not Found" {"message": "Customer interaction not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"customer_id":1,"user_id":1,"type":"call",...}}
     * @response 404 scenario="Not Found" {"message": "Customer interaction not found."}
     * @response 422 scenario="Validation Error" {"message":"The customer_id field is required.","errors":{"customer_id":["The customer_id field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateCustomerInteractionRequest $request, CustomerInteraction $customer_interaction)
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
     * @response 404 scenario="Not Found" {"message": "Customer interaction not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(CustomerInteraction $customer_interaction)
    {
        $customer_interaction->delete();

        return response()->noContent();
    }
}
