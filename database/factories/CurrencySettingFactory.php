<?php

namespace Database\Factories;

use App\Models\CurrencySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrencySetting>
 */
class CurrencySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en_US',
        ];
    }
}
