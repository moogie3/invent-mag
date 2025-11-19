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
     */
    public function store(Request $request)
    {
        //
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
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
