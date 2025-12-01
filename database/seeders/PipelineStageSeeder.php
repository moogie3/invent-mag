<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PipelineStage;
use App\Models\SalesPipeline;

class PipelineStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pipeline = SalesPipeline::where('is_default', true)->first();

        if ($pipeline) {
            PipelineStage::create([
                'sales_pipeline_id' => $pipeline->id,
                'name' => 'Qualification',
                'position' => 1,
                'is_closed' => false,
            ]);

            PipelineStage::create([
                'sales_pipeline_id' => $pipeline->id,
                'name' => 'Proposal',
                'position' => 2,
                'is_closed' => false,
            ]);

            PipelineStage::create([
                'sales_pipeline_id' => $pipeline->id,
                'name' => 'Negotiation',
                'position' => 3,
                'is_closed' => false,
            ]);

            PipelineStage::create([
                'sales_pipeline_id' => $pipeline->id,
'name' => 'Won',
                'position' => 4,
                'is_closed' => true,
            ]);

            PipelineStage::create([
                'sales_pipeline_id' => $pipeline->id,
                'name' => 'Lost',
                'position' => 5,
                'is_closed' => true,
            ]);
        }
    }
}
