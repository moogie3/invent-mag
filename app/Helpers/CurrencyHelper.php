<?php

namespace App\Helpers;

use App\Models\CurrencySetting;

class CurrencyHelper
{
    protected static $settings = null;

    /**
     * Format amount with currency settings
     */
    public static function format($amount)
    {
        return self::formatWithPosition($amount);
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
    public static function getSettings()
    {
        if (self::$settings === null) {
            self::$settings = CurrencySetting::first() ?? self::getDefaultSettings();
        }

        return self::$settings;
    }

    /**
     * Default fallback settings
     */
    public static function getDefaultSettings()
    {
        return (object) [
            'currency_code' => 'IDR',
            'currency_symbol' => 'Rp',
            'decimal_places' => 0,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'prefix',
            'locale' => 'id-ID',
        ];
    }

    /**
     * Clear the cached settings for testing purposes.
     */
    public static function clearSettingsCache()
    {
        self::$settings = null;
    }

    /**
     * Set settings for testing purposes.
     */
    public static function setSettingsForTesting($settings)
    {
        self::$settings = $settings;
    }

    /**
     * Format amount with currency symbol in correct position
     */
    public static function formatWithPosition($amount)
    {
        $settings = self::getSettings();
        $formatted = number_format(
            (float) $amount,
            $settings->decimal_places,
            $settings->decimal_separator,
            $settings->thousand_separator
        );

        return $settings->position === 'prefix'
            ? $settings->currency_symbol . ' ' . $formatted
            : $formatted . ' ' . $settings->currency_symbol;
    }

    public static function formatDate($date)
    {
        return \Carbon\Carbon::parse($date)->format('d M Y');
    }
}