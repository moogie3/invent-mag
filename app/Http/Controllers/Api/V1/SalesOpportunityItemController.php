<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOpportunityItemResource;
use App\Models\SalesOpportunityItem;
use Illuminate\Http\Request;

/**
 * @group Sales Opportunity Items
 *
 * APIs for managing sales opportunity items
 */
class SalesOpportunityItemController extends Controller
{
    /**
     * Display a listing of the sales opportunity items.
     *
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = SalesOpportunityItem::with(['opportunity', 'product'])->paginate($perPage);
        return SalesOpportunityItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified sales opportunity item.
     *
     * @urlParam sales_opportunity_item required The ID of the sales opportunity item. Example: 1
     */
    public function show(SalesOpportunityItem $sales_opportunity_item)
    {
        return new SalesOpportunityItemResource($sales_opportunity_item->load(['opportunity', 'product']));
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
