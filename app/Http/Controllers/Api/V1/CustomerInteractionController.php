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
     */
    public function store(Request $request)
    {
        //
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
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
