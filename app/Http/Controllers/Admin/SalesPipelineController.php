<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\SalesPipeline;
use App\Models\PipelineStage;
use App\Models\SalesOpportunity;
use Illuminate\Validation\Rule;
use App\Services\SalesPipelineService;

class SalesPipelineController extends Controller
{
    protected $salesPipelineService;

    public function __construct(SalesPipelineService $salesPipelineService)
    {
        $this->salesPipelineService = $salesPipelineService;
    }

    public function index()
    {
        $data = $this->salesPipelineService->getSalesPipelineIndexData();

        return view('admin.sales.pipeline', $data);
    }

    public function indexPipelines()
    {
        $pipelines = SalesPipeline::with('stages')->get();
        return response()->json($pipelines);
    }

    public function storePipeline(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sales_pipelines',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $pipeline = $this->salesPipelineService->createPipeline($request->all());
        return response()->json($pipeline, 201);
    }

    public function updatePipeline(Request $request, SalesPipeline $pipeline)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sales_pipelines')->ignore($pipeline->id)],
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $pipeline = $this->salesPipelineService->updatePipeline($pipeline, $request->all());
        return response()->json($pipeline);
    }

    public function destroyPipeline(SalesPipeline $pipeline)
    {
        $this->salesPipelineService->deletePipeline($pipeline);
        return redirect()->route('admin.sales_pipeline.index')->with('success', 'Pipeline deleted successfully!');
    }

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

    public function updateStage(Request $request, PipelineStage $stage)
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

    public function destroyStage(PipelineStage $stage)
    {
        $this->salesPipelineService->deleteStage($stage);
        return redirect()->route('admin.sales_pipeline.index')->with('success', 'Stage deleted successfully!');
    }

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

    public function indexOpportunities(Request $request)
    {
        $opportunitiesQuery = SalesOpportunity::with(['customer', 'pipeline', 'stage']);

        if ($request->pipeline_id) {
            $opportunitiesQuery->where('sales_pipeline_id', $request->pipeline_id);
        }

        if ($request->stage_id) {
            $opportunitiesQuery->where('pipeline_stage_id', $request->stage_id);
        }

        $opportunities = $opportunitiesQuery->get();
        $totalPipelineValue = $opportunities->sum('amount');

        return response()->json([
            'opportunities' => $opportunities,
            'total_pipeline_value' => $totalPipelineValue,
        ]);
    }

    public function storeOpportunity(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|in:open,won,lost',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            $opportunity = $this->salesPipelineService->createOpportunity($validatedData);
            return response()->json($opportunity->load('items'), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create opportunity: ' . $e->getMessage(), 'type' => 'error'], 500);
        }
    }

    public function updateOpportunity(Request $request, SalesOpportunity $opportunity)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|in:open,won,lost',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            $opportunity = $this->salesPipelineService->updateOpportunity($opportunity, $validatedData);
            return response()->json($opportunity->load('items'), 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update opportunity: ' . $e->getMessage(), 'type' => 'error'], 500);
        }
    }

    public function destroyOpportunity(SalesOpportunity $opportunity)
    {
        $this->salesPipelineService->deleteOpportunity($opportunity);
        return redirect()->route('admin.sales_pipeline.index')->with('success', 'Opportunity deleted successfully!');
    }

    public function showOpportunity(SalesOpportunity $opportunity)
    {
        try {
            return response()->json($opportunity->load('customer', 'pipeline', 'stage', 'items.product'));
        } catch (\Exception $e) {return response()->json(['message' => 'Failed to fetch opportunity details.', 'error' => $e->getMessage()], 500);
        }
    }

    public function moveOpportunity(Request $request, SalesOpportunity $opportunity)
    {
        $request->validate([
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
        ]);

        $opportunity = $this->salesPipelineService->moveOpportunity($opportunity, $request->pipeline_stage_id);
        return response()->json($opportunity);
    }

    public function showConvertForm(SalesOpportunity $opportunity)
    {
        return view('admin.layouts.modals.sales-pipeline-modals', compact('opportunity'));
    }

    public function convertToSalesOrder(SalesOpportunity $opportunity)
    {
        try {
            $salesOrder = $this->salesPipelineService->convertToSalesOrder($opportunity);
            return response()->json(['message' => 'Opportunity successfully converted to Sales Order.', 'type' => 'success', 'sales_id' => $salesOrder->id], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to convert opportunity: ' . $e->getMessage(), 'type' => 'error'], 500);
        }
    }
}