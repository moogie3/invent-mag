<?php

namespace App\Services;

use App\Models\CurrencySetting;

use App\Models\Unit;

class CurrencyService
{
    public function getCurrencyEditData(int $entries)
    {
        $setting = CurrencySetting::first();
        $units = Unit::paginate($entries);
        $totalunit = Unit::count();
        return compact('setting', 'units', 'entries', 'totalunit');
    }

    public function updateCurrency(array $data)
    {
        $setting = CurrencySetting::first();
        $setting->update($data);

        return ['success' => true, 'message' => 'Currency settings updated successfully.'];
    }
}
