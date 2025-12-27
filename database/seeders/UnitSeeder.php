<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::updateOrCreate(
            ['name' => 'Pieces'],
            ['symbol' => 'PCS']
        );

        Unit::updateOrCreate(
            ['name' => 'Roll'],
            ['symbol' => 'Roll']
        );

        Unit::updateOrCreate(
            ['name' => 'Meters'],
            ['symbol' => 'M']
        );
    }
}
