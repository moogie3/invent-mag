<?php

namespace App\Helpers;

use App\Models\CurrencySetting;
use App\Models\PlatformCurrencySetting;
use Carbon\Carbon;

class CurrencyHelper
{
    protected static $settings = null;

    protected static $platformSettings = null;

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
        $isNegative = $amount < 0;
        $absoluteAmount = abs($amount);

        $formatted = number_format(
            (float) $absoluteAmount, // Use absolute amount here
            $settings->decimal_places,
            $settings->decimal_separator,
            $settings->thousand_separator
        );

        $result = $settings->position === 'prefix'
            ? $settings->currency_symbol.' '.$formatted
            : $formatted.' '.$settings->currency_symbol;

        return $isNegative ? '-'.$result : $result;
    }

    /**
     * Format amount with platform currency settings (super admin context)
     * Completely independent from tenant currency settings
     */
    public static function formatPlatform($amount)
    {
        return self::formatWithPositionPlatform($amount);
    }

    /**
     * Get platform currency settings with caching
     */
    public static function getPlatformSettings()
    {
        if (self::$platformSettings === null) {
            self::$platformSettings = PlatformCurrencySetting::first() ?? self::getDefaultPlatformSettings();
        }

        return self::$platformSettings;
    }

    /**
     * Default platform settings (USD)
     */
    public static function getDefaultPlatformSettings()
    {
        return (object) [
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix',
        ];
    }

    /**
     * Clear the cached platform settings for testing purposes.
     */
    public static function clearPlatformSettingsCache()
    {
        self::$platformSettings = null;
    }

    /**
     * Set platform settings for testing purposes.
     */
    public static function setPlatformSettingsForTesting($settings)
    {
        self::$platformSettings = $settings;
    }

    /**
     * Format amount with platform currency symbol in correct position
     */
    public static function formatWithPositionPlatform($amount)
    {
        $settings = self::getPlatformSettings();
        $isNegative = $amount < 0;
        $absoluteAmount = abs($amount);

        $formatted = number_format(
            (float) $absoluteAmount,
            $settings->decimal_places,
            $settings->decimal_separator,
            $settings->thousand_separator
        );

        $result = $settings->position === 'prefix'
            ? $settings->currency_symbol.' '.$formatted
            : $formatted.' '.$settings->currency_symbol;

        return $isNegative ? '-'.$result : $result;
    }

    /**
     * Format an IDR amount with Indonesian conventions (super admin context).
     * Subscription orders and revenue are stored in IDR, so they must never be
     * formatted with the (typically USD) platform display currency.
     */
    public static function formatPlatformIdr($amount)
    {
        $isNegative = $amount < 0;
        $formatted = number_format((float) abs($amount), 0, ',', '.');

        return ($isNegative ? '-' : '').'Rp '.$formatted;
    }

    /**
     * Format discount amount per unit with proper decimal places (not rounded)
     * Used for displaying percentage discount converted to fixed amount
     */
    public static function formatDiscountPerUnit($amount)
    {
        return number_format((float) $amount, 1, '.', '');
    }

    public static function formatDate($date)
    {
        return Carbon::parse($date)->format('d M Y');
    }
}
