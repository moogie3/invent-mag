<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesItemResource;
use App\Models\SalesItem;
use Illuminate\Http\Request;

/**
 * @group Sales Items
 *
 * APIs for managing sales items
 */
class SalesItemController extends Controller
{
    /**
     * Display a listing of the sales items.
     *
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = SalesItem::with(['sales', 'product'])->paginate($perPage);
        return SalesItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified sales item.
     *
     * @urlParam sales_item required The ID of the sales item. Example: 1
     */
    public function show(SalesItem $sales_item)
    {
        return new SalesItemResource($sales_item->load(['sales', 'product']));
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
