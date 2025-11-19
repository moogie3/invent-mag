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
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $taxes = Tax::paginate($perPage);
        return TaxResource::collection($taxes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified tax.
     *
     * @urlParam tax required The ID of the tax. Example: 1
     */
    public function show(Tax $tax)
    {
        return new TaxResource($tax);
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
