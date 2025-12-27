<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class CurrencySetting extends Model
{
    use HasFactory, BelongsToTenant;
    protected $table = 'currency_settings';
    protected $fillable = ['tenant_id', 'currency_symbol', 'decimal_separator', 'thousand_separator', 'decimal_places', 'position', 'currency_code', 'locale'];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\CurrencySettingFactory::new();
    }
}