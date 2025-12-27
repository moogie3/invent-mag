<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUnitRequest;
use App\Http\Requests\Api\V1\UpdateUnitRequest;
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
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Piece","symbol":"pc",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 201 scenario="Success" {"data":{"id":1,"name":"Piece","symbol":"pc",...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreUnitRequest $request)
    {
        $result = $this->unitService->createUnit($request->validated());

        return new UnitResource($result['unit']);
    }

    /**
     * Display the specified unit.
     *
     * @group Units
     * @authenticated
     * @urlParam unit required The ID of the unit. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Piece","symbol":"pc",...}}
     * @response 404 scenario="Not Found" {"message": "Unit not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Kilogram","symbol":"kg",...}}
     * @response 404 scenario="Not Found" {"message": "Unit not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $result = $this->unitService->updateUnit($unit, $request->validated());

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
     * @response 404 scenario="Not Found" {"message": "Unit not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Unit $unit)
    {
        $this->unitService->deleteUnit($unit);

        return response()->noContent();
    }
}
