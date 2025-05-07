<?php

namespace App\Helpers;

use App\Models\CurrencySetting;

class CurrencyHelper
{
    /**
     * Format amount with currency settings
     */
    public static function format($amount)
    {
        $settings = self::getSettings();

        return $settings->currency_symbol . ' ' .
            number_format(
                $amount,
                $settings->decimal_places,
                $settings->decimal_separator,
                $settings->thousand_separator
            );
    }

    /**
     * Get currency symbol only
     */
    public static function getCurrencySymbol()
    {
        return self::getSettings()->currency_symbol;
    }

    /**
     * Get currency position (prefix/suffix)
     */
    public static function getCurrencyPosition()
    {
        return self::getSettings()->position;
    }

    /**
     * Get all currency settings with caching
     */
    protected static function getSettings()
    {
        static $settings = null;

        if ($settings === null) {
            $settings = CurrencySetting::first() ?? self::getDefaultSettings();
        }

        return $settings;
    }

    /**
     * Default fallback settings
     */
    protected static function getDefaultSettings()
    {
        return (object) [
            'currency_symbol' => 'Rp',
            'decimal_places' => 0,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'prefix'
        ];
    }

    /**
     * Format amount with currency symbol in correct position
     */
    public static function formatWithPosition($amount)
    {
        $settings = self::getSettings();
        $formatted = number_format(
            $amount,
            $settings->decimal_places,
            $settings->decimal_separator,
            $settings->thousand_separator
        );

        return $settings->position === 'prefix'
            ? $settings->currency_symbol . ' ' . $formatted
            : $formatted . ' ' . $settings->currency_symbol;
    }
}
