<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesPipelineResource;
use App\Models\SalesPipeline;
use Illuminate\Http\Request;

/**
 * @group Sales Pipelines
 *
 * APIs for managing sales pipelines
 */
class SalesPipelineController extends Controller
{
    /**
     * Display a listing of the sales pipelines.
     *
     * @queryParam per_page int The number of pipelines to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $pipelines = SalesPipeline::with(['stages', 'opportunities'])->paginate($perPage);
        return SalesPipelineResource::collection($pipelines);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam name string required The name of the sales pipeline. Example: Initial Sales Pipeline
     * @bodyParam description string A description of the sales pipeline. Example: Pipeline for initial customer contact.
     * @bodyParam is_default boolean Is this the default pipeline. Example: false
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "name": "Initial Sales Pipeline",
     *         "description": "Pipeline for initial customer contact.",
     *         "is_default": false,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $sales_pipeline = SalesPipeline::create($validated);

        return new SalesPipelineResource($sales_pipeline);
    }

    /**
     * Display the specified sales pipeline.
     *
     * @urlParam sales_pipeline required The ID of the sales pipeline. Example: 1
     */
    public function show(SalesPipeline $sales_pipeline)
    {
        return new SalesPipelineResource($sales_pipeline->load(['stages', 'opportunities']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam sales_pipeline integer required The ID of the sales pipeline. Example: 1
     * @bodyParam name string required The name of the sales pipeline. Example: New Sales Pipeline
     * @bodyParam description string A description of the sales pipeline. Example: Pipeline for new leads.
     * @bodyParam is_default boolean Is this the default pipeline. Example: true
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "name": "New Sales Pipeline",
     *         "description": "Pipeline for new leads.",
     *         "is_default": true,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, SalesPipeline $sales_pipeline)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $sales_pipeline->update($validated);

        return new SalesPipelineResource($sales_pipeline);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam sales_pipeline integer required The ID of the sales pipeline to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(SalesPipeline $sales_pipeline)
    {
        $sales_pipeline->delete();

        return response()->noContent();
    }
}
