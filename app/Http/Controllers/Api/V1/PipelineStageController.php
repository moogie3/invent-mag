<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * @responseField id integer The ID of the pipeline stage.
     * @responseField sales_pipeline_id integer The ID of the sales pipeline this stage belongs to.
     * @responseField name string The name of the stage.
     * @responseField position integer The position of the stage.
     * @responseField is_closed boolean Whether this stage is a closed stage.
     * @responseField created_at string The date and time the stage was created.
     * @responseField updated_at string The date and time the stage was last updated.
     * @response 200 scenario="Success" {"id":1,"sales_pipeline_id":1,"name":"Negotiation","position":1,"is_closed":false,"created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z"}
     */
    public function update(\App\Http\Requests\Api\V1\UpdatePipelineStageRequest $request, PipelineStage $stage)
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
     */
    public function destroy(PipelineStage $stage)
    {
        $this->salesPipelineService->deleteStage($stage);
        return response()->noContent();
    }
}
