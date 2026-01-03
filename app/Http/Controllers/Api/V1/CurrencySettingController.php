<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateCurrencySettingRequest;
use App\Http\Resources\CurrencySettingResource;
use App\Models\CurrencySetting;
use App\Services\CurrencyService;

/**
 * @group Currency Settings
 *
 * APIs for managing currency settings
 */
class CurrencySettingController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }
    /**
     * Display a listing of the currency settings.
     *
     * @group Currency Settings
     * @authenticated
     * @queryParam per_page int The number of settings to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":{"id":1,"currency_symbol":"$","decimal_separator":".",...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index()
    {
        $setting = CurrencySetting::first();
        return new CurrencySettingResource($setting);
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Currency Settings
     * @authenticated
     * @urlParam currency_setting required The ID of the currency setting. Example: 1
     * @bodyParam currency_symbol string required The currency symbol. Example: "$"
     * @bodyParam decimal_separator string required The decimal separator. Example: "."
     * @bodyParam thousand_separator string required The thousand separator. Example: ","
     * @bodyParam decimal_places integer required The number of decimal places. Example: 2
     * @bodyParam position string required The position of the currency symbol. Example: "prefix"
     * @bodyParam currency_code string required The currency code. Example: "USD"
     * @bodyParam locale string required The locale. Example: "en_US"
     *
     * @response 200 scenario="Success" {"data":{"id":1,"currency_symbol":"¥","decimal_separator":",",...}}
     * @response 404 scenario="Not Found" {"message": "Currency setting not found."}
     * @response 422 scenario="Validation Error" {"message":"The currency_symbol field is required.","errors":{"currency_symbol":["The currency_symbol field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateCurrencySettingRequest $request)
    {
        $result = $this->currencyService->updateCurrency($request->validated());

        return new CurrencySettingResource(CurrencySetting::first());
    }
}
