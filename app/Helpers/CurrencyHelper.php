<?php

namespace App\Helpers;

use App\Models\CurrencySetting;

class CurrencyHelper {
    public static function format($amount) {
        $settings = CurrencySetting::first();
        if (!$settings) {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }

        return $settings->currency_symbol . ' ' .
        number_format($amount, $settings->decimal_places, $settings->decimal_separator, $settings->thousand_separator);
    }
}