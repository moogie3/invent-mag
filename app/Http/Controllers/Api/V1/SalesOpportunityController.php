<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOpportunityResource;
use App\Models\SalesOpportunity;
use Illuminate\Http\Request;

/**
 * @group Sales Opportunities
 *
 * APIs for managing sales opportunities
 */
class SalesOpportunityController extends Controller
{
    /**
     * Display a listing of the sales opportunities.
     *
     * @queryParam per_page int The number of opportunities to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $opportunities = SalesOpportunity::with(['pipeline', 'stage', 'customer', 'items'])->paginate($perPage);
        return SalesOpportunityResource::collection($opportunities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified sales opportunity.
     *
     * @urlParam sales_opportunity required The ID of the sales opportunity. Example: 1
     */
    public function show(SalesOpportunity $sales_opportunity)
    {
        return new SalesOpportunityResource($sales_opportunity->load(['pipeline', 'stage', 'customer', 'items']));
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
