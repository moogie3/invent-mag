<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalesPipeline;

class SalesPipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SalesPipeline::create([
            'name' => 'Default Pipeline',
            'description' => 'The default sales pipeline.',
            'is_default' => true,
        ]);
    }
}
