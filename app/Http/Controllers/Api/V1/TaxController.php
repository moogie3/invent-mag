<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateTaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Http\Request;

/**
 * @group Taxes
 *
 * APIs for managing taxes
 */
class TaxController extends Controller
{
    protected $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }
    /**
     * Display a listing of the taxes.
     *
     * @group Taxes
     * @authenticated
     * @queryParam per_page int The number of taxes to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"VAT","rate":0.1,...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index()
    {
        $tax = $this->taxService->getTaxData();
        return new TaxResource($tax);
    }

    /**
     * Display the specified tax.
     *
     * @group Taxes
     * @authenticated
     * @urlParam tax required The ID of the tax. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"VAT","rate":0.1,"is_active":true,...}}
     * @response 404 scenario="Not Found" {"message": "Tax not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Tax $tax)
    {
        return new TaxResource($this->taxService->getTaxData());
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Taxes
     * @authenticated
     * @urlParam tax required The ID of the tax. Example: 1
     * @bodyParam name string required The name of the tax. Example: VAT
     * @bodyParam rate numeric required The tax rate. Example: 0.10
     * @bodyParam is_active boolean Is the tax active. Example: true
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"VAT","rate":0.10,"is_active":true,...}}
     * @response 404 scenario="Not Found" {"message": "Tax not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateTaxRequest $request)
    {
        $result = $this->taxService->updateTax($request->validated());

        return new TaxResource($this->taxService->getTaxData());
    }

    /**
     * Get Active Tax Rate
     *
     * @group Taxes
     * @authenticated
     *
     * @response 200 scenario="Success" {"tax_rate":0.10}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getActiveTax()
    {
        $tax = Tax::where('is_active', 1)->first();
        return response()->json(['tax_rate' => $tax ? $tax->rate : 0]);
    }
}
