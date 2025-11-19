<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\POSResource;
use App\Models\POS;
use Illuminate\Http\Request;

/**
 * @group POS
 *
 * APIs for managing Point of Sale
 */
class POSController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        // The POS model is currently a placeholder.
        return POSResource::collection([]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @urlParam po required The ID of the resource.
     */
    public function show(POS $po)
    {
        // The POS model is currently a placeholder.
        return new POSResource($po);
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
