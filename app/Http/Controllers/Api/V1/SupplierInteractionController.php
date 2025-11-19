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
     */
    public function store(Request $request)
    {
        //
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
