<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOpportunityResource;
use App\Models\SalesOpportunity;
use App\Services\SalesPipelineService;
use Illuminate\Http\Request;

/**
 * @group Sales Opportunities
 *
 * APIs for managing sales opportunities
 */
class SalesOpportunityController extends Controller
{
    protected $salesPipelineService;

    public function __construct(SalesPipelineService $salesPipelineService)
    {
        $this->salesPipelineService = $salesPipelineService;
    }

    /**
     * Display a listing of the sales opportunities.
     *
     * @group Sales Opportunities
     * @authenticated
     * @queryParam per_page int The number of opportunities to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of sales opportunities.
     * @responseField data[].id integer The ID of the sales opportunity.
     * @responseField data[].customer_id integer The ID of the customer.
     * @responseField data[].sales_pipeline_id integer The ID of the sales pipeline.
     * @responseField data[].pipeline_stage_id integer The ID of the pipeline stage.
     * @responseField data[].name string The name of the sales opportunity.
     * @responseField data[].description string The description of the sales opportunity.
     * @responseField data[].amount number The estimated amount of the opportunity.
     * @responseField data[].expected_close_date string The expected close date of the opportunity.
     * @responseField data[].status string The status of the opportunity (e.g., Open, Won, Lost).
     * @responseField data[].sales_id integer The ID of the associated sales record.
     * @responseField data[].created_at string The date and time the opportunity was created.
     * @responseField data[].updated_at string The date and time the opportunity was last updated.
     * @responseField data[].pipeline object The sales pipeline.
     * @responseField data[].stage object The pipeline stage.
     * @responseField data[].customer object The customer.
     * @responseField data[].items object[] The items in the opportunity.
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
        $data = $this->salesPipelineService->getOpportunitiesByFilters($request);
        return SalesOpportunityResource::collection($data['opportunities']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Sales Opportunities
     * @authenticated
     * @bodyParam customer_id integer required The ID of the customer. Example: 1
     * @bodyParam sales_pipeline_id integer required The ID of the sales pipeline. Example: 1
     * @bodyParam pipeline_stage_id integer required The ID of the pipeline stage. Example: 1
     * @bodyParam name string required The name of the sales opportunity. Example: New Client Project
     * @bodyParam description string A description of the sales opportunity. Example: Develop a new e-commerce platform.
     * @bodyParam amount numeric The estimated amount of the opportunity. Example: 50000.00
     * @bodyParam expected_close_date date The expected close date of the opportunity. Example: 2024-12-31
     * @bodyParam status string The status of the opportunity (e.g., Open, Won, Lost). Example: Open
     * @bodyParam items array required A list of product items for the opportunity.
     * @bodyParam items.*.product_id integer required The ID of the product. Example: 1
     * @bodyParam items.*.quantity integer required The quantity of the product. Example: 1
     * @bodyParam items.*.price numeric required The price of the product. Example: 100.00
     *
     * @responseField id integer The ID of the sales opportunity.
     * @responseField customer_id integer The ID of the customer.
     * @responseField sales_pipeline_id integer The ID of the sales pipeline.
     * @responseField pipeline_stage_id integer The ID of the pipeline stage.
     * @responseField name string The name of the sales opportunity.
     * @responseField description string The description of the sales opportunity.
     * @responseField amount number The estimated amount of the opportunity.
     * @responseField expected_close_date string The expected close date of the opportunity.
     * @responseField status string The status of the opportunity (e.g., Open, Won, Lost).
     * @responseField sales_id integer The ID of the associated sales record.
     * @responseField created_at string The date and time the opportunity was created.
     * @responseField updated_at string The date and time the opportunity was last updated.
     * @responseField items object[] The items in the opportunity.
     */
    public function store(\App\Http\Requests\Api\V1\StoreSalesOpportunityRequest $request)
    {
        $opportunity = $this->salesPipelineService->createOpportunity($request->validated());
        return new SalesOpportunityResource($opportunity->load('items'));
    }

    /**
     * Display the specified sales opportunity.
     *
     * @group Sales Opportunities
     * @authenticated
     * @urlParam sales_opportunity required The ID of the sales opportunity. Example: 1
     *
     * @responseField id integer The ID of the sales opportunity.
     * @responseField customer_id integer The ID of the customer.
     * @responseField sales_pipeline_id integer The ID of the sales pipeline.
     * @responseField pipeline_stage_id integer The ID of the pipeline stage.
     * @responseField name string The name of the sales opportunity.
     * @responseField description string The description of the sales opportunity.
     * @responseField amount number The estimated amount of the opportunity.
     * @responseField expected_close_date string The expected close date of the opportunity.
     * @responseField status string The status of the opportunity (e.g., Open, Won, Lost).
     * @responseField sales_id integer The ID of the associated sales record.
     * @responseField created_at string The date and time the opportunity was created.
     * @responseField updated_at string The date and time the opportunity was last updated.
     * @responseField pipeline object The sales pipeline.
     * @responseField stage object The pipeline stage.
     * @responseField customer object The customer.
     * @responseField items object[] The items in the opportunity.
     */
    public function show(SalesOpportunity $sales_opportunity)
    {
        return new SalesOpportunityResource($sales_opportunity->load(['pipeline', 'stage', 'customer', 'items']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Sales Opportunities
     * @authenticated
     * @urlParam sales_opportunity integer required The ID of the sales opportunity. Example: 1
     * @bodyParam customer_id integer required The ID of the customer. Example: 1
     * @bodyParam sales_pipeline_id integer required The ID of the sales pipeline. Example: 1
     * @bodyParam pipeline_stage_id integer required The ID of the pipeline stage. Example: 1
     * @bodyParam name string required The name of the sales opportunity. Example: New Client Project
     * @bodyParam description string A description of the sales opportunity. Example: Develop a new e-commerce platform.
     * @bodyParam amount numeric The estimated amount of the opportunity. Example: 50000.00
     * @bodyParam expected_close_date date The expected close date of the opportunity. Example: 2024-12-31
     * @bodyParam status string The status of the opportunity (e.g., Open, Won, Lost). Example: Open
     * @bodyParam items array required A list of product items for the opportunity.
     * @bodyParam items.*.product_id integer required The ID of the product. Example: 1
     * @bodyParam items.*.quantity integer required The quantity of the product. Example: 1
     * @bodyParam items.*.price numeric required The price of the product. Example: 100.00
     *
     * @responseField id integer The ID of the sales opportunity.
     * @responseField customer_id integer The ID of the customer.
     * @responseField sales_pipeline_id integer The ID of the sales pipeline.
     * @responseField pipeline_stage_id integer The ID of the pipeline stage.
     * @responseField name string The name of the sales opportunity.
     * @responseField description string The description of the sales opportunity.
     * @responseField amount number The estimated amount of the opportunity.
     * @responseField expected_close_date string The expected close date of the opportunity.
     * @responseField status string The status of the opportunity (e.g., Open, Won, Lost).
     * @responseField sales_id integer The ID of the associated sales record.
     * @responseField created_at string The date and time the opportunity was created.
     * @responseField updated_at string The date and time the opportunity was last updated.
     * @responseField items object[] The items in the opportunity.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateSalesOpportunityRequest $request, SalesOpportunity $sales_opportunity)
    {
        $opportunity = $this->salesPipelineService->updateOpportunity($sales_opportunity, $request->validated());
        return new SalesOpportunityResource($opportunity->load('items'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Sales Opportunities
     * @authenticated
     * @urlParam sales_opportunity integer required The ID of the sales opportunity to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(SalesOpportunity $sales_opportunity)
    {
        $this->salesPipelineService->deleteOpportunity($sales_opportunity);

        return response()->noContent();
    }

    /**
     * Move Opportunity to a new Stage
     *
     * @group Sales Opportunities
     * @authenticated
     * @urlParam opportunity integer required The ID of the sales opportunity. Example: 1
     * @bodyParam pipeline_stage_id integer required The ID of the new pipeline stage. Example: 2
     *
     * @responseField id integer The ID of the sales opportunity.
     * @responseField customer_id integer The ID of the customer.
     * @responseField sales_pipeline_id integer The ID of the sales pipeline.
     * @responseField pipeline_stage_id integer The ID of the pipeline stage.
     * @responseField name string The name of the sales opportunity.
     * @responseField description string The description of the sales opportunity.
     * @responseField amount number The estimated amount of the opportunity.
     * @responseField expected_close_date string The expected close date of the opportunity.
     * @responseField status string The status of the opportunity (e.g., Open, Won, Lost).
     * @responseField sales_id integer The ID of the associated sales record.
     * @responseField created_at string The date and time the opportunity was created.
     * @responseField updated_at string The date and time the opportunity was last updated.
     */
    public function moveOpportunity(\App\Http\Requests\Api\V1\MoveSalesOpportunityRequest $request, SalesOpportunity $opportunity)
    {
        $opportunity = $this->salesPipelineService->moveOpportunity($opportunity, $request->validated()['pipeline_stage_id']);
        return new SalesOpportunityResource($opportunity);
    }

    /**
     * Convert Opportunity to Sales Order
     *
     * @group Sales Opportunities
     * @authenticated
     * @urlParam opportunity integer required The ID of the sales opportunity. Example: 1
     *
     * @responseField message string A message indicating the result.
     * @responseField type string The type of response (e.g., "success", "error").
     * @responseField sales_id integer The ID of the newly created sales order.
     */
    public function convertToSalesOrder(SalesOpportunity $opportunity)
    {
        $salesOrder = $this->salesPipelineService->convertToSalesOrder($opportunity);
        return response()->json(['message' => 'Opportunity successfully converted to Sales Order.', 'type' => 'success', 'sales_id' => $salesOrder->id], 200);
    }
}
