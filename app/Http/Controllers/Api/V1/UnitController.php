<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;

/**
 * @group Units
 *
 * APIs for managing units
 */
class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     *
     * @queryParam per_page int The number of units to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $units = Unit::paginate($perPage);
        return UnitResource::collection($units);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam name string required The name of the unit. Example: Piece
     * @bodyParam symbol string required The symbol of the unit. Example: pc
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "name": "Piece",
     *         "symbol": "pc",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        $unit = Unit::create($validated);

        return new UnitResource($unit);
    }

    /**
     * Display the specified unit.
     *
     * @urlParam unit required The ID of the unit. Example: 1
     */
    public function show(Unit $unit)
    {
        return new UnitResource($unit);
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam unit integer required The ID of the unit. Example: 1
     * @bodyParam name string required The name of the unit. Example: Kilogram
     * @bodyParam symbol string required The symbol of the unit. Example: kg
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "name": "Kilogram",
     *         "symbol": "kg",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        $unit->update($validated);

        return new UnitResource($unit);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam unit integer required The ID of the unit to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->noContent();
    }
}
