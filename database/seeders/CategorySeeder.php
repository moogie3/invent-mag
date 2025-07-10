<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            DB::table('categories')->insert([
            [
                'name' => 'TR',
                'description' => 'Transistor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'FBT',
                'description' => 'Flyback',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'IC',
                'description' => 'Integrated Circuit',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}