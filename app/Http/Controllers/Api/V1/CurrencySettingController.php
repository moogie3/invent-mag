<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * @responseField id integer The ID of the currency setting.
     * @responseField currency_symbol string The currency symbol.
     * @responseField decimal_separator string The decimal separator.
     * @responseField thousand_separator string The thousand separator.
     * @responseField decimal_places integer The number of decimal places.
     * @responseField position string The position of the currency symbol.
     * @responseField currency_code string The currency code.
     * @responseField locale string The locale.
     * @responseField created_at string The date and time the setting was created.
     * @responseField updated_at string The date and time the setting was last updated.
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
     * @responseField id integer The ID of the currency setting.
     * @responseField currency_symbol string The currency symbol.
     * @responseField decimal_separator string The decimal separator.
     * @responseField thousand_separator string The thousand separator.
     * @responseField decimal_places integer The number of decimal places.
     * @responseField position string The position of the currency symbol.
     * @responseField currency_code string The currency code.
     * @responseField locale string The locale.
     * @responseField created_at string The date and time the setting was created.
     * @responseField updated_at string The date and time the setting was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateCurrencySettingRequest $request)
    {
        $result = $this->currencyService->updateCurrency($request->validated());

        return new CurrencySettingResource(CurrencySetting::first());
    }
}
