<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrencySettingResource;
use App\Models\CurrencySetting;
use Illuminate\Http\Request;

/**
 * @group Currency Settings
 *
 * APIs for managing currency settings
 */
class CurrencySettingController extends Controller
{
    /**
     * Display a listing of the currency settings.
     *
     * @queryParam per_page int The number of settings to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $settings = CurrencySetting::paginate($perPage);
        return CurrencySettingResource::collection($settings);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @response 201 scenario="Success"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'currency_symbol' => 'required|string|max:255',
            'decimal_separator' => 'required|string|max:1',
            'thousand_separator' => 'required|string|max:1',
            'decimal_places' => 'required|integer',
            'position' => 'required|string|max:255',
            'currency_code' => 'required|string|max:255',
            'locale' => 'required|string|max:255',
        ]);

        $currency_setting = CurrencySetting::create($validated);

        return new CurrencySettingResource($currency_setting);
    }

    /**
     * Display the specified currency setting.
     *
     * @urlParam currency_setting required The ID of the currency setting. Example: 1
     */
    public function show(CurrencySetting $currency_setting)
    {
        return new CurrencySettingResource($currency_setting);
    }

    /**
     * Update the specified resource in storage.
     *
     * @response 200 scenario="Success"
     */
    public function update(Request $request, CurrencySetting $currency_setting)
    {
        $validated = $request->validate([
            'currency_symbol' => 'required|string|max:255',
            'decimal_separator' => 'required|string|max:1',
            'thousand_separator' => 'required|string|max:1',
            'decimal_places' => 'required|integer',
            'position' => 'required|string|max:255',
            'currency_code' => 'required|string|max:255',
            'locale' => 'required|string|max:255',
        ]);

        $currency_setting->update($validated);

        return new CurrencySettingResource($currency_setting);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @response 204 scenario="Success"
     */
    public function destroy(CurrencySetting $currency_setting)
    {
        $currency_setting->delete();

        return response()->noContent();
    }
}
