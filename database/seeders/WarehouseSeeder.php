<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        // updateOrCreate() fires the updated event even when nothing changed,
        // and the activity log listener records it, so a second run logged
        // three warehouse updates while creating no new rows.
        if (Warehouse::where('tenant_id', $tenantId)->exists()) {
            if (isset($this->command)) {
                $this->command->info('Warehouses already seeded for tenant '.$tenantId.', skipping.');
            }

            return;
        }

        Warehouse::updateOrCreate(
            ['name' => 'Gudang Utama Jakarta', 'tenant_id' => $tenantId],
            [
                'address' => 'Jl. Raya Cakung No. 10, Jakarta Timur',
                'description' => 'Gudang utama untuk distribusi pusat.',
                'is_main' => true,
            ]
        );

        Warehouse::updateOrCreate(
            ['name' => 'Gudang Surabaya', 'tenant_id' => $tenantId],
            [
                'address' => 'Jl. Ahmad Yani No. 50, Surabaya',
                'description' => 'Gudang cabang di Jawa Timur.',
                'is_main' => false,
            ]
        );

        Warehouse::updateOrCreate(
            ['name' => 'Gudang Bandung', 'tenant_id' => $tenantId],
            [
                'address' => 'Jl. Pasteur No. 23, Bandung',
                'description' => 'Gudang cabang untuk area Jawa Barat.',
                'is_main' => false,
            ]
        );
    }
}
