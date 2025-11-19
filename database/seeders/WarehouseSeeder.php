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
        Warehouse::updateOrCreate(
            ['name' => 'Gudang Utama Jakarta'],
            [
                'address' => 'Jl. Raya Cakung No. 10, Jakarta Timur',
                'description' => 'Gudang utama untuk distribusi pusat.',
                'is_main' => true,
            ]
        );

        Warehouse::updateOrCreate(
            ['name' => 'Gudang Surabaya'],
            [
                'address' => 'Jl. Ahmad Yani No. 50, Surabaya',
                'description' => 'Gudang cabang di Jawa Timur.',
                'is_main' => false,
            ]
        );

        Warehouse::updateOrCreate(
            ['name' => 'Gudang Bandung'],
            [
                'address' => 'Jl. Pasteur No. 23, Bandung',
                'description' => 'Gudang cabang untuk area Jawa Barat.',
                'is_main' => false,
            ]
        );
    }
}
