<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformCurrencySetting extends Model
{
    protected $table = 'platform_currency_settings';

    protected $fillable = [
        'currency_symbol',
        'decimal_separator',
        'thousand_separator',
        'decimal_places',
        'position',
        'currency_code',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
    ];
}
