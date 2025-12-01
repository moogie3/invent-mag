<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\Request;

/**
 * @group Units
 *
 * APIs for managing units
 */
class UnitController extends Controller
{
    protected $unitService;

    public function __construct(\App\Services\UnitService $unitService)
    {
        $this->unitService = $unitService;
    }
    /**
     * Display a listing of the units.
     *
     * @group Units
     * @authenticated
     * @queryParam per_page int The number of units to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of units.
     * @responseField data[].id integer The ID of the unit.
     * @responseField data[].name string The name of the unit.
     * @responseField data[].symbol string The symbol of the unit.
     * @responseField data[].created_at string The date and time the unit was created.
     * @responseField data[].updated_at string The date and time the unit was last updated.
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
        $entries = $request->input('per_page', 15);
        $data = $this->unitService->getUnitIndexData($entries);
        return UnitResource::collection($data['units']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Units
     * @authenticated
     * @bodyParam name string required The name of the unit. Example: Piece
     * @bodyParam symbol string required The symbol of the unit. Example: pc
     *
     * @responseField id integer The ID of the unit.
     * @responseField name string The name of the unit.
     * @responseField symbol string The symbol of the unit.
     * @responseField created_at string The date and time the unit was created.
     * @responseField updated_at string The date and time the unit was last updated.
     * @response 422 scenario="Creation Failed" {"success": false, "message": "Failed to create unit."}
     */
    public function store(\App\Http\Requests\Api\V1\StoreUnitRequest $request)
    {
        $result = $this->unitService->createUnit($request->validated());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        return new UnitResource($result['unit']);
    }

    /**
     * Display the specified unit.
     *
     * @group Units
     * @authenticated
     * @urlParam unit required The ID of the unit. Example: 1
     *
     * @responseField id integer The ID of the unit.
     * @responseField name string The name of the unit.
     * @responseField symbol string The symbol of the unit.
     * @responseField created_at string The date and time the unit was created.
     * @responseField updated_at string The date and time the unit was last updated.
     */
    public function show(Unit $unit)
    {
        return new UnitResource($unit);
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Units
     * @authenticated
     * @urlParam unit integer required The ID of the unit. Example: 1
     * @bodyParam name string required The name of the unit. Example: Kilogram
     * @bodyParam symbol string required The symbol of the unit. Example: kg
     *
     * @responseField id integer The ID of the unit.
     * @responseField name string The name of the unit.
     * @responseField symbol string The symbol of the unit.
     * @responseField created_at string The date and time the unit was created.
     * @responseField updated_at string The date and time the unit was last updated.
     * @response 422 scenario="Update Failed" {"success": false, "message": "Failed to update unit."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdateUnitRequest $request, Unit $unit)
    {
        $result = $this->unitService->updateUnit($unit, $request->validated());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        return new UnitResource($result['unit']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Units
     * @authenticated
     * @urlParam unit integer required The ID of the unit to delete. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 500 scenario="Deletion Failed" {"success": false, "message": "Failed to delete unit."}
     */
    public function destroy(Unit $unit)
    {
        $result = $this->unitService->deleteUnit($unit);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }

        return response()->noContent();
    }
}
