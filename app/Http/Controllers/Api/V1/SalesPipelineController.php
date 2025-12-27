<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSalesPipelineRequest;
use App\Http\Requests\Api\V1\UpdateSalesPipelineRequest;
use App\Http\Resources\SalesPipelineResource;
use App\Http\Requests\Api\V1\StorePipelineStageRequest;
use App\Http\Requests\Api\V1\ReorderPipelineStagesRequest;
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
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Main Pipeline",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 201 scenario="Success" {"data":{"id":1,"name":"Initial Sales Pipeline",...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreSalesPipelineRequest $request)
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Main Pipeline","description":"Main sales pipeline.","is_default":true,...}}
     * @response 404 scenario="Not Found" {"message": "Sales pipeline not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"New Sales Pipeline",...}}
     * @response 404 scenario="Not Found" {"message": "Sales pipeline not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateSalesPipelineRequest $request, SalesPipeline $sales_pipeline)
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
     * @response 404 scenario="Not Found" {"message": "Sales pipeline not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 201 scenario="Stage Created" {"id":1,"sales_pipeline_id":1,"name":"Qualification","position":0,"is_closed":false,"created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z"}
     * @response 404 scenario="Pipeline Not Found" {"message": "Sales pipeline not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function storeStage(StorePipelineStageRequest $request, SalesPipeline $pipeline)
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
     * @response 200 scenario="Success" {"message": "Stages reordered successfully"}
     * @response 404 scenario="Pipeline Not Found" {"message": "Sales pipeline not found."}
     * @response 422 scenario="Validation Error" {"message":"The stages field is required.","errors":{"stages":["The stages field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function reorderStages(ReorderPipelineStagesRequest $request, SalesPipeline $pipeline)
    {
        $this->salesPipelineService->reorderStages($pipeline, $request->validated()['stages']);

        return response()->json(['message' => 'Stages reordered successfully']);
    }
}
