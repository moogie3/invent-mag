<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tax::create([
            'name' => 'No Tax',
            'rate' => 0,
            'is_active' => true,
        ]);

        Tax::create([
            'name' => 'VAT',
            'rate' => 10,
            'is_active' => false,
        ]);
    }
}
