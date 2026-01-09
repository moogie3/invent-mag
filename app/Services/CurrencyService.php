<?php

namespace App\Services;

use App\Models\CurrencySetting;
use App\Models\Unit;

class CurrencyService
{
    public function getCurrencyEditData(int $entries)
    {
        $setting = CurrencySetting::firstOrCreate([]);
        $units = Unit::paginate($entries);
        $totalunit = Unit::count();
        $predefinedCurrencies = $this->getPredefinedCurrencies();

        return compact('setting', 'units', 'entries', 'totalunit', 'predefinedCurrencies');
    }

    public function updateCurrency(array $data)
    {
        CurrencySetting::updateOrCreate([], $data);
        \App\Helpers\CurrencyHelper::clearSettingsCache();

        return ['success' => true, 'message' => 'Currency settings updated successfully.'];
    }

    private function getPredefinedCurrencies()
    {
        return [
            ['name' => 'United States Dollar', 'code' => 'USD', 'locale' => 'en-US', 'symbol' => '$'],
            ['name' => 'Euro', 'code' => 'EUR', 'locale' => 'en-EU', 'symbol' => '€'],
            ['name' => 'Japanese Yen', 'code' => 'JPY', 'locale' => 'ja-JP', 'symbol' => '¥'],
            ['name' => 'British Pound', 'code' => 'GBP', 'locale' => 'en-GB', 'symbol' => '£'],
            ['name' => 'Australian Dollar', 'code' => 'AUD', 'locale' => 'en-AU', 'symbol' => 'A$'],
            ['name' => 'Canadian Dollar', 'code' => 'CAD', 'locale' => 'en-CA', 'symbol' => 'C$'],
            ['name' => 'Swiss Franc', 'code' => 'CHF', 'locale' => 'de-CH', 'symbol' => 'CHF'],
            ['name' => 'Chinese Yuan', 'code' => 'CNY', 'locale' => 'zh-CN', 'symbol' => '¥'],
            ['name' => 'Swedish Krona', 'code' => 'SEK', 'locale' => 'sv-SE', 'symbol' => 'kr'],
            ['name' => 'New Zealand Dollar', 'code' => 'NZD', 'locale' => 'en-NZ', 'symbol' => 'NZ$'],
            ['name' => 'Mexican Peso', 'code' => 'MXN', 'locale' => 'es-MX', 'symbol' => 'Mex$'],
            ['name' => 'Singapore Dollar', 'code' => 'SGD', 'locale' => 'en-SG', 'symbol' => 'S$'],
            ['name' => 'Hong Kong Dollar', 'code' => 'HKD', 'locale' => 'en-HK', 'symbol' => 'HK$'],
            ['name' => 'Norwegian Krone', 'code' => 'NOK', 'locale' => 'nb-NO', 'symbol' => 'kr'],
            ['name' => 'South Korean Won', 'code' => 'KRW', 'locale' => 'ko-KR', 'symbol' => '₩'],
            ['name' => 'Turkish Lira', 'code' => 'TRY', 'locale' => 'tr-TR', 'symbol' => '₺'],
            ['name' => 'Russian Ruble', 'code' => 'RUB', 'locale' => 'ru-RU', 'symbol' => '₽'],
            ['name' => 'Indian Rupee', 'code' => 'INR', 'locale' => 'en-IN', 'symbol' => '₹'],
            ['name' => 'Brazilian Real', 'code' => 'BRL', 'locale' => 'pt-BR', 'symbol' => 'R$'],
            ['name' => 'South African Rand', 'code' => 'ZAR', 'locale' => 'en-ZA', 'symbol' => 'R'],
            ['name' => 'Indonesian Rupiah', 'code' => 'IDR', 'locale' => 'id-ID', 'symbol' => 'Rp'],
        ];
    }
}
