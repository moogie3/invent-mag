<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CurrencySettingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencySetting extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'currency_settings';

    protected $fillable = ['currency_symbol', 'decimal_separator', 'thousand_separator', 'decimal_places', 'position', 'currency_code', 'locale'];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return CurrencySettingFactory::new();
    }
}
