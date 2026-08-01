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

        SalesPipeline::updateOrCreate(
            ['name' => 'Default Pipeline - '.$tenantName, 'tenant_id' => $tenantId],
            [
                'description' => 'The default sales pipeline.',
                'is_default' => true,
            ]
        );
    }
}
