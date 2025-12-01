<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

    public function __construct(\App\Services\TaxService $taxService)
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
     * @responseField id integer The ID of the tax.
     * @responseField name string The name of the tax.
     * @responseField rate number The tax rate.
     * @responseField is_active boolean Whether the tax is active.
     * @responseField created_at string The date and time the tax was created.
     * @responseField updated_at string The date and time the tax was last updated.
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
     * @responseField id integer The ID of the tax.
     * @responseField name string The name of the tax.
     * @responseField rate number The tax rate.
     * @responseField is_active boolean Whether the tax is active.
     * @responseField created_at string The date and time the tax was created.
     * @responseField updated_at string The date and time the tax was last updated.
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
     * @bodyParam name string required The name of the tax. Example: VAT
     * @bodyParam rate numeric required The tax rate. Example: 0.10
     * @bodyParam is_active boolean Is the tax active. Example: true
     *
     * @responseField id integer The ID of the tax.
     * @responseField name string The name of the tax.
     * @responseField rate number The tax rate.
     * @responseField is_active boolean Whether the tax is active.
     * @responseField created_at string The date and time the tax was created.
     * @responseField updated_at string The date and time the tax was last updated.
     * @response 500 scenario="Update Failed" {"success": false, "message": "Failed to update tax."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdateTaxRequest $request)
    {
        $result = $this->taxService->updateTax($request->validated());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }

        return new TaxResource($this->taxService->getTaxData());
    }

    /**
     * Get Active Tax Rate
     *
     * @group Taxes
     * @authenticated
     *
     * @responseField tax_rate number The active tax rate.
     */
    public function getActiveTax()
    {
        $tax = \App\Models\Tax::where('is_active', 1)->first();
        return response()->json(['tax_rate' => $tax ? $tax->rate : 0]);
    }
}