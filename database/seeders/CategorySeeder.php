<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categories;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Categories::create([
            'name' => 'TR',
            'description' => 'Transistor',
        ]);
        Categories::create([
            'name' => 'FBT',
            'description' => 'Flyback',
        ]);
        Categories::create([
            'name' => 'IC',
            'description' => 'Integrated Circuit',
        ]);
    }
}