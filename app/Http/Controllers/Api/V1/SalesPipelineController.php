<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesPipelineResource;
use App\Models\SalesPipeline;
use App\Services\SalesPipelineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Sales Pipelines
 *
 * APIs for managing sales pipelines
 */
class SalesPipelineController extends Controller
{
    protected $salesPipelineService;

    public function __construct(SalesPipelineService $salesPipelineService)
    {
        $this->salesPipelineService = $salesPipelineService;
    }

    /**
     * Display a listing of the sales pipelines.
     *
     * @queryParam per_page int The number of pipelines to return per page. Defaults to 15. Example: 25
     *
     * @apiResourceCollection App\Http\Resources\SalesPipelineResource
     * @apiResourceModel App\Models\SalesPipeline
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
            'name' => 'required|string|max:255|unique:sales_pipelines',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $sales_pipeline = $this->salesPipelineService->createPipeline($validated);

        return new SalesPipelineResource($sales_pipeline);
    }

    /**
     * Display the specified sales pipeline.
     *
     * @urlParam sales_pipeline required The ID of the sales pipeline. Example: 1
     *
     * @apiResource App\Http\Resources\SalesPipelineResource
     * @apiResourceModel App\Models\SalesPipeline
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
            'name' => ['required', 'string', 'max:255', Rule::unique('sales_pipelines')->ignore($sales_pipeline->id)],
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $sales_pipeline = $this->salesPipelineService->updatePipeline($sales_pipeline, $validated);

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
        $this->salesPipelineService->deletePipeline($sales_pipeline);

        return response()->noContent();
    }

    /**
     * @group Sales Pipelines
     * @title Add Stage to Pipeline
     * @urlParam pipeline integer required The ID of the sales pipeline. Example: 1
     * @bodyParam name string required The name of the new stage. Example: "Qualification"
     * @bodyParam is_closed boolean Whether this stage represents a closed state. Example: false
     *
     * @response 201 {
     *  "id": 1,
     *  "name": "Qualification",
     *  "sales_pipeline_id": 1,
     *  ...
     * }
     */
    public function storeStage(Request $request, SalesPipeline $pipeline)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('pipeline_stages')->where(function ($query) use ($pipeline) {
                return $query->where('sales_pipeline_id', $pipeline->id);
            })],
            'is_closed' => 'boolean',
        ]);

        $stage = $this->salesPipelineService->createStage($pipeline, $request->all());
        return response()->json($stage, 201);
    }

    /**
     * @group Sales Pipelines
     * @title Reorder Stages in Pipeline
     * @urlParam pipeline integer required The ID of the sales pipeline. Example: 1
     * @bodyParam stages array required An array of stage objects with id and new position.
     * @bodyParam stages.*.id integer required The ID of the stage. Example: 1
     * @bodyParam stages.*.position integer required The new position of the stage. Example: 0
     *
     * @response {
     *  "message": "Stages reordered successfully"
     * }
     */
    public function reorderStages(Request $request, SalesPipeline $pipeline)
    {
        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.position' => 'required|integer|min:0',
        ]);

        $this->salesPipelineService->reorderStages($pipeline, $request->stages);

        return response()->json(['message' => 'Stages reordered successfully']);
    }
}
