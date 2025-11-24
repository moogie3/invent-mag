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
     * @group Sales Pipeline Stages
     * @title Update a Pipeline Stage
     * @urlParam stage integer required The ID of the pipeline stage to update. Example: 1
     * @bodyParam name string required The new name of the stage. Example: "Negotiation"
     * @bodyParam position integer required The new position of the stage. Example: 1
     * @bodyParam is_closed boolean Whether this stage represents a closed state. Example: false
     *
     * @response {
     *  "id": 1,
     *  "name": "Negotiation",
     *  ...
     * }
     */
    public function update(Request $request, PipelineStage $stage)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('pipeline_stages')->ignore($stage->id)->where(function ($query) use ($stage) {
                return $query->where('sales_pipeline_id', $stage->sales_pipeline_id);
            })],
            'position' => 'required|integer|min:0',
            'is_closed' => 'boolean',
        ]);

        $stage = $this->salesPipelineService->updateStage($stage, $request->all());
        return response()->json($stage);
    }

    /**
     * @group Sales Pipeline Stages
     * @title Delete a Pipeline Stage
     * @urlParam stage integer required The ID of the pipeline stage to delete. Example: 1
     *
     * @response 204
     */
    public function destroy(PipelineStage $stage)
    {
        $this->salesPipelineService->deleteStage($stage);
        return response()->noContent();
    }
}