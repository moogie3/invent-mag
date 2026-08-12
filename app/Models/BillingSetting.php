<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    protected $fillable = [
        'usd_idr_rate',
        'rate_updated_at',
    ];

    protected $casts = [
        'usd_idr_rate' => 'decimal:2',
        'rate_updated_at' => 'datetime',
    ];

    /**
     * Get the current USD → IDR billing rate.
     * Falls back to the configured default when no row exists yet.
     */
    public static function getRate(): float
    {
        $setting = static::first();

        return (float) ($setting?->usd_idr_rate ?? config('billing.fallback_rate', 16000));
    }

    /**
     * Store a new USD → IDR billing rate (single row).
     */
    public static function setRate(float $rate): self
    {
        $setting = static::first();

        if ($setting) {
            $setting->update([
                'usd_idr_rate' => $rate,
                'rate_updated_at' => now(),
            ]);

            return $setting;
        }

        return static::create([
            'usd_idr_rate' => $rate,
            'rate_updated_at' => now(),
        ]);
    }
}
