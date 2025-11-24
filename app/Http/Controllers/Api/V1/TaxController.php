<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\Request;

/**
 * @group Taxes
 *
 * APIs for managing taxes
 */
class TaxController extends Controller
{
    /**
     * Display a listing of the taxes.
     *
     * @queryParam per_page int The number of taxes to return per page. Defaults to 15. Example: 25
     *
     * @apiResourceCollection App\Http\Resources\TaxResource
     * @apiResourceModel App\Models\Tax
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $taxes = Tax::paginate($perPage);
        return TaxResource::collection($taxes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam name string required The name of the tax. Example: Sales Tax
     * @bodyParam rate numeric required The tax rate. Example: 0.05
     * @bodyParam is_active boolean Is the tax active. Example: true
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "name": "Sales Tax",
     *         "rate": 0.05,
     *         "is_active": true,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $tax = Tax::create($validated);

        return new TaxResource($tax);
    }

    /**
     * Display the specified tax.
     *
     * @urlParam tax required The ID of the tax. Example: 1
     *
     * @apiResource App\Http\Resources\TaxResource
     * @apiResourceModel App\Models\Tax
     */
    public function show(Tax $tax)
    {
        return new TaxResource($tax);
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam tax integer required The ID of the tax. Example: 1
     * @bodyParam name string required The name of the tax. Example: VAT
     * @bodyParam rate numeric required The tax rate. Example: 0.10
     * @bodyParam is_active boolean Is the tax active. Example: true
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "name": "VAT",
     *         "rate": 0.10,
     *         "is_active": true,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Tax $tax)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $tax->update($validated);

        return new TaxResource($tax);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam tax integer required The ID of the tax to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Tax $tax)
    {
        $tax->delete();

        return response()->noContent();
    }

    /**
     * @group Taxes
     * @title Get Active Tax Rate
     *
     * @response {
     *  "tax_rate": 0.1
     * }
     */
    public function getActiveTax()
    {
        $tax = \App\Models\Tax::where('is_active', 1)->first();
        return response()->json(['tax_rate' => $tax ? $tax->rate : 0]);
    }
}
