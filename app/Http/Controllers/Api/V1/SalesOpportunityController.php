<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSalesOpportunityRequest;
use App\Http\Requests\Api\V1\UpdateSalesOpportunityRequest;
use App\Http\Requests\Api\V1\MoveSalesOpportunityRequest;
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
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"New Client Project","amount":50000,...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"New Client Project (Updated)",...}}
     * @response 404 scenario="Not Found" {"message": "Sales opportunity not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateSalesOpportunityRequest $request, SalesOpportunity $sales_opportunity)
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
     * @response 404 scenario="Not Found" {"message": "Sales opportunity not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"New Client Project","pipeline_stage_id":2,...}}
     * @response 404 scenario="Opportunity or Stage Not Found" {"message": "Sales opportunity or stage not found."}
     * @response 422 scenario="Validation Error" {"message":"The pipeline_stage_id field is required.","errors":{"pipeline_stage_id":["The pipeline_stage_id field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function moveOpportunity(MoveSalesOpportunityRequest $request, SalesOpportunity $opportunity)
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
     * @response 200 scenario="Success" {"message": "Opportunity successfully converted to Sales Order.", "type": "success", "sales_id": 1}
     * @response 404 scenario="Opportunity Not Found" {"message": "Sales opportunity not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Error converting to Sales Order" {"message": "Failed to convert opportunity to sales order: Internal server error.", "type": "error"}
     */
    public function convertToSalesOrder(SalesOpportunity $opportunity)
    {
        $salesOrder = $this->salesPipelineService->convertToSalesOrder($opportunity);
        return response()->json(['message' => 'Opportunity successfully converted to Sales Order.', 'type' => 'success', 'sales_id' => $salesOrder->id], 200);
    }
}
