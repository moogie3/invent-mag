<?php

namespace Database\Seeders;

use App\Models\SalesPipeline;
use Illuminate\Database\Seeder;

class SalesPipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $tenantName = app('currentTenant')->name;

        // updateOrCreate() fires the updated event even when nothing is
        // dirty, so a re-run wrote an activity log row and nothing else.
        if (SalesPipeline::where('tenant_id', $tenantId)->exists()) {
            $this->command->info("Default pipeline already seeded for tenant {$tenantName}, skipping.");

            return;
        }

        SalesPipeline::updateOrCreate(
            ['name' => 'Default Pipeline - '.$tenantName, 'tenant_id' => $tenantId],
            [
                'description' => 'The default sales pipeline.',
                'is_default' => true,
            ]
        );
    }
}
