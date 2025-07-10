<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            DB::table('currency_settings')->insert([
            'currency_symbol' => 'Rp',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 0,
        ]);
    }
}
