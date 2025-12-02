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
     * @group Sales Pipelines
     * @authenticated
     * @queryParam per_page int The number of pipelines to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of sales pipelines.
     * @responseField data[].id integer The ID of the sales pipeline.
     * @responseField data[].name string The name of the sales pipeline.
     * @responseField data[].description string The description of the sales pipeline.
     * @responseField data[].is_default boolean Whether this is the default pipeline.
     * @responseField data[].created_at string The date and time the pipeline was created.
     * @responseField data[].updated_at string The date and time the pipeline was last updated.
     * @responseField data[].stages object[] A list of stages in the pipeline.
     * @responseField data[].opportunities object[] A list of sales opportunities in the pipeline.
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
        $data = $this->salesPipelineService->getSalesPipelineIndexData();
        return SalesPipelineResource::collection($data['pipelines']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Sales Pipelines
     * @authenticated
     * @bodyParam name string required The name of the sales pipeline. Example: Initial Sales Pipeline
     * @bodyParam description string A description of the sales pipeline. Example: Pipeline for initial customer contact.
     * @bodyParam is_default boolean Is this the default pipeline. Example: false
     *
     * @responseField id integer The ID of the sales pipeline.
     * @responseField name string The name of the sales pipeline.
     * @responseField description string The description of the sales pipeline.
     * @responseField is_default boolean Whether this is the default pipeline.
     * @responseField created_at string The date and time the pipeline was created.
     * @responseField updated_at string The date and time the pipeline was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreSalesPipelineRequest $request)
    {
        $sales_pipeline = $this->salesPipelineService->createPipeline($request->validated());

        return new SalesPipelineResource($sales_pipeline);
    }

    /**
     * Display the specified sales pipeline.
     *
     * @group Sales Pipelines
     * @authenticated
     * @urlParam sales_pipeline required The ID of the sales pipeline. Example: 1
     *
     * @responseField id integer The ID of the sales pipeline.
     * @responseField name string The name of the sales pipeline.
     * @responseField description string The description of the sales pipeline.
     * @responseField is_default boolean Whether this is the default pipeline.
     * @responseField created_at string The date and time the pipeline was created.
     * @responseField updated_at string The date and time the pipeline was last updated.
     * @responseField stages object[] A list of stages in the pipeline.
     * @responseField opportunities object[] A list of sales opportunities in the pipeline.
     */
    public function show(SalesPipeline $sales_pipeline)
    {
        return new SalesPipelineResource($sales_pipeline->load(['stages', 'opportunities']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Sales Pipelines
     * @authenticated
     * @urlParam sales_pipeline integer required The ID of the sales pipeline. Example: 1
     * @bodyParam name string required The name of the sales pipeline. Example: New Sales Pipeline
     * @bodyParam description string A description of the sales pipeline. Example: Pipeline for new leads.
     * @bodyParam is_default boolean Is this the default pipeline. Example: true
     *
     * @responseField id integer The ID of the sales pipeline.
     * @responseField name string The name of the sales pipeline.
     * @responseField description string The description of the sales pipeline.
     * @responseField is_default boolean Whether this is the default pipeline.
     * @responseField created_at string The date and time the pipeline was created.
     * @responseField updated_at string The date and time the pipeline was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateSalesPipelineRequest $request, SalesPipeline $sales_pipeline)
    {
        $sales_pipeline = $this->salesPipelineService->updatePipeline($sales_pipeline, $request->validated());

        return new SalesPipelineResource($sales_pipeline);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Sales Pipelines
     * @authenticated
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
     * Add Stage to Pipeline
     *
     * @group Sales Pipelines
     * @authenticated
     * @urlParam pipeline integer required The ID of the sales pipeline. Example: 1
     * @bodyParam name string required The name of the new stage. Example: "Qualification"
     * @bodyParam is_closed boolean Whether this stage represents a closed state. Example: false
     *
     * @responseField id integer The ID of the pipeline stage.
     * @responseField sales_pipeline_id integer The ID of the sales pipeline this stage belongs to.
     * @responseField name string The name of the stage.
     * @responseField position integer The position of the stage.
     * @responseField is_closed boolean Whether this stage is a closed stage.
     * @responseField created_at string The date and time the stage was created.
     * @responseField updated_at string The date and time the stage was last updated.
     * @response 201 scenario="Stage Created" {"id":1,"sales_pipeline_id":1,"name":"Qualification","position":0,"is_closed":false,"created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z"}
     */
    public function storeStage(\App\Http\Requests\Api\V1\StorePipelineStageRequest $request, SalesPipeline $pipeline)
    {
        $stage = $this->salesPipelineService->createStage($pipeline, $request->validated());
        return response()->json($stage, 201);
    }

    /**
     * Reorder Stages in Pipeline
     *
     * @group Sales Pipelines
     * @authenticated
     * @urlParam pipeline integer required The ID of the sales pipeline. Example: 1
     * @bodyParam stages array required An array of stage objects with id and new position.
     * @bodyParam stages.*.id integer required The ID of the stage. Example: 1
     * @bodyParam stages.*.position integer required The new position of the stage. Example: 0
     *
     * @responseField message string A message indicating the result of the reordering.
     */
    public function reorderStages(\App\Http\Requests\Api\V1\ReorderPipelineStagesRequest $request, SalesPipeline $pipeline)
    {
        $this->salesPipelineService->reorderStages($pipeline, $request->validated()['stages']);

        return response()->json(['message' => 'Stages reordered successfully']);
    }
}
