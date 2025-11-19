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
     */
    public function store(Request $request)
    {
        //
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
