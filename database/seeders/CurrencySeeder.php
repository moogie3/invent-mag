<?php

namespace Database\Seeders;

use App\Models\CurrencySetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CurrencySetting::updateOrCreate(
            ['id' => 1], // Assuming there is only one setting row
            [
                'currency_symbol' => 'Rp',
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'decimal_places' => 0,
                'position' => 'prefix',
                'currency_code' => 'IDR',
                'locale' => 'id-ID',
            ]
        );
    }
}