<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOpportunityResource;
use App\Models\SalesOpportunity;
use Illuminate\Http\Request;

/**
 * @group Sales Opportunities
 *
 * APIs for managing sales opportunities
 */
class SalesOpportunityController extends Controller
{
    /**
     * Display a listing of the sales opportunities.
     *
     * @queryParam per_page int The number of opportunities to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $opportunities = SalesOpportunity::with(['pipeline', 'stage', 'customer', 'items'])->paginate($perPage);
        return SalesOpportunityResource::collection($opportunities);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam customer_id integer required The ID of the customer. Example: 1
     * @bodyParam sales_pipeline_id integer required The ID of the sales pipeline. Example: 1
     * @bodyParam pipeline_stage_id integer required The ID of the pipeline stage. Example: 1
     * @bodyParam name string required The name of the sales opportunity. Example: New Client Project
     * @bodyParam description string A description of the sales opportunity. Example: Develop a new e-commerce platform.
     * @bodyParam amount numeric The estimated amount of the opportunity. Example: 50000.00
     * @bodyParam expected_close_date date The expected close date of the opportunity. Example: 2024-12-31
     * @bodyParam status string The status of the opportunity (e.g., Open, Won, Lost). Example: Open
     * @bodyParam sales_id integer The ID of the associated sales record. Example: 1
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "customer_id": 1,
     *         "sales_pipeline_id": 1,
     *         "pipeline_stage_id": 1,
     *         "name": "New Client Project",
     *         "description": "Develop a new e-commerce platform.",
     *         "amount": 50000.00,
     *         "expected_close_date": "2024-12-31",
     *         "status": "Open",
     *         "sales_id": 1,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|string|max:255',
            'sales_id' => 'nullable|exists:sales,id',
        ]);

        $sales_opportunity = SalesOpportunity::create($validated);

        return new SalesOpportunityResource($sales_opportunity);
    }

    /**
     * Display the specified sales opportunity.
     *
     * @urlParam sales_opportunity required The ID of the sales opportunity. Example: 1
     */
    public function show(SalesOpportunity $sales_opportunity)
    {
        return new SalesOpportunityResource($sales_opportunity->load(['pipeline', 'stage', 'customer', 'items']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam sales_opportunity integer required The ID of the sales opportunity. Example: 1
     * @bodyParam customer_id integer required The ID of the customer. Example: 1
     * @bodyParam sales_pipeline_id integer required The ID of the sales pipeline. Example: 1
     * @bodyParam pipeline_stage_id integer required The ID of the pipeline stage. Example: 1
     * @bodyParam name string required The name of the sales opportunity. Example: New Client Project
     * @bodyParam description string A description of the sales opportunity. Example: Develop a new e-commerce platform.
     * @bodyParam amount numeric The estimated amount of the opportunity. Example: 50000.00
     * @bodyParam expected_close_date date The expected close date of the opportunity. Example: 2024-12-31
     * @bodyParam status string The status of the opportunity (e.g., Open, Won, Lost). Example: Open
     * @bodyParam sales_id integer The ID of the associated sales record. Example: 1
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "customer_id": 1,
     *         "sales_pipeline_id": 1,
     *         "pipeline_stage_id": 1,
     *         "name": "New Client Project",
     *         "description": "Develop a new e-commerce platform.",
     *         "amount": 50000.00,
     *         "expected_close_date": "2024-12-31",
     *         "status": "Open",
     *         "sales_id": 1,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, SalesOpportunity $sales_opportunity)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|string|max:255',
            'sales_id' => 'nullable|exists:sales,id',
        ]);

        $sales_opportunity->update($validated);

        return new SalesOpportunityResource($sales_opportunity);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam sales_opportunity integer required The ID of the sales opportunity to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(SalesOpportunity $sales_opportunity)
    {
        $sales_opportunity->delete();

        return response()->noContent();
    }
}
