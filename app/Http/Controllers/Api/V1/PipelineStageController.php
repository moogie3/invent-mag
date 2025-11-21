<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PipelineStageResource;
use App\Models\PipelineStage;
use Illuminate\Http\Request;

/**
 * @group Pipeline Stages
 *
 * APIs for managing pipeline stages
 */
class PipelineStageController extends Controller
{
    /**
     * Display a listing of the pipeline stages.
     *
     * @queryParam per_page int The number of stages to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $stages = PipelineStage::with('pipeline')->paginate($perPage);
        return PipelineStageResource::collection($stages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @response 201 scenario="Success"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'name' => 'required|string|max:255',
            'position' => 'required|integer',
            'is_closed' => 'required|boolean',
        ]);

        $pipeline_stage = PipelineStage::create($validated);

        return new PipelineStageResource($pipeline_stage);
    }

    /**
     * Display the specified pipeline stage.
     *
     * @urlParam pipeline_stage required The ID of the pipeline stage. Example: 1
     */
    public function show(PipelineStage $pipeline_stage)
    {
        return new PipelineStageResource($pipeline_stage->load('pipeline'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @response 200 scenario="Success"
     */
    public function update(Request $request, PipelineStage $pipeline_stage)
    {
        $validated = $request->validate([
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'name' => 'required|string|max:255',
            'position' => 'required|integer',
            'is_closed' => 'required|boolean',
        ]);

        $pipeline_stage->update($validated);

        return new PipelineStageResource($pipeline_stage);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @response 204 scenario="Success"
     */
    public function destroy(PipelineStage $pipeline_stage)
    {
        $pipeline_stage->delete();

        return response()->noContent();
    }
}
