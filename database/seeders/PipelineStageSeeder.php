<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use App\Models\SalesPipeline;
use Illuminate\Database\Seeder;

class PipelineStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $pipeline = SalesPipeline::where('is_default', true)->where('tenant_id', $tenantId)->first();

        // Same reason as SalesPipelineSeeder: the five updateOrCreate() calls
        // below are no-ops on a second run but still each write an activity
        // log row.
        if (PipelineStage::where('tenant_id', $tenantId)->exists()) {
            if (isset($this->command)) {
                $this->command->info('Pipeline stages already seeded for tenant '.$tenantId.', skipping.');
            }

            return;
        }

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
        }
    }
}
