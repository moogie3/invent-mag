<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePipelineStageRequest;
use App\Models\PipelineStage;
use App\Services\SalesPipelineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Sales Pipeline Stages
 *
 * APIs for managing individual pipeline stages
 */
class PipelineStageController extends Controller
{
    protected $salesPipelineService;

    public function __construct(SalesPipelineService $salesPipelineService)
    {
        $this->salesPipelineService = $salesPipelineService;
    }

    /**
     * Update a Pipeline Stage
     *
     * @group Sales Pipeline Stages
     * @authenticated
     * @urlParam stage integer required The ID of the pipeline stage to update. Example: 1
     * @bodyParam name string required The new name of the stage. Example: Negotiation
     * @bodyParam position integer required The new position of the stage. Example: 1
     * @bodyParam is_closed boolean Whether this stage represents a closed state. Example: false
     *
     * @response 200 scenario="Success" {"id":1,"sales_pipeline_id":1,"name":"Negotiation","position":1,"is_closed":false,"created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z"}
     * @response 404 scenario="Not Found" {"message": "Pipeline stage not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdatePipelineStageRequest $request, PipelineStage $stage)
    {
        $stage = $this->salesPipelineService->updateStage($stage, $request->validated());
        return response()->json($stage);
    }

    /**
     * Delete a Pipeline Stage
     *
     * @group Sales Pipeline Stages
     * @authenticated
     * @urlParam stage integer required The ID of the pipeline stage to delete. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Pipeline stage not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(PipelineStage $stage)
    {
        $this->salesPipelineService->deleteStage($stage);
        return response()->noContent();
    }
}
