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
            $tenantId = app('currentTenant')->id;
            $pipeline = SalesPipeline::where('is_default', true)->where('tenant_id', $tenantId)->first();
    
            if ($pipeline) {
                PipelineStage::updateOrCreate(
                    ['sales_pipeline_id' => $pipeline->id, 'name' => 'Qualification', 'tenant_id' => $tenantId],
                    ['position' => 1, 'is_closed' => false]
                );
    
                PipelineStage::updateOrCreate(
                    ['sales_pipeline_id' => $pipeline->id, 'name' => 'Proposal', 'tenant_id' => $tenantId],
                    ['position' => 2, 'is_closed' => false]
                );
    
                PipelineStage::updateOrCreate(
                    ['sales_pipeline_id' => $pipeline->id, 'name' => 'Negotiation', 'tenant_id' => $tenantId],
                    ['position' => 3, 'is_closed' => false]
                );
    
                PipelineStage::updateOrCreate(
                    ['sales_pipeline_id' => $pipeline->id, 'name' => 'Won', 'tenant_id' => $tenantId],
                    ['position' => 4, 'is_closed' => true]
                );
    
                PipelineStage::updateOrCreate(
                    ['sales_pipeline_id' => $pipeline->id, 'name' => 'Lost', 'tenant_id' => $tenantId],
                    ['position' => 5, 'is_closed' => true]
                );
            }    }
}
