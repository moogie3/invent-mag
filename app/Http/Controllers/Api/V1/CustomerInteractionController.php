<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerInteractionResource;
use App\Models\CustomerInteraction;
use Illuminate\Http\Request;

/**
 * @group Customer Interactions
 *
 * APIs for managing customer interactions
 */
class CustomerInteractionController extends Controller
{
    /**
     * Display a listing of the customer interactions.
     *
     * @queryParam per_page int The number of interactions to return per page. Defaults to 15. Example: 25
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
     * @response 201 scenario="Success"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'interaction_date' => 'required|date',
        ]);

        $customer_interaction = CustomerInteraction::create($validated);

        return new CustomerInteractionResource($customer_interaction);
    }

    /**
     * Display the specified customer interaction.
     *
     * @urlParam customer_interaction required The ID of the customer interaction. Example: 1
     */
    public function show(CustomerInteraction $customer_interaction)
    {
        return new CustomerInteractionResource($customer_interaction->load('customer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @response 200 scenario="Success"
     */
    public function update(Request $request, CustomerInteraction $customer_interaction)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'interaction_date' => 'required|date',
        ]);

        $customer_interaction->update($validated);

        return new CustomerInteractionResource($customer_interaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @response 204 scenario="Success"
     */
    public function destroy(CustomerInteraction $customer_interaction)
    {
        $customer_interaction->delete();

        return response()->noContent();
    }
}
