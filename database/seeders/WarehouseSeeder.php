<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('warehouses')->insert([
            [
                'name' => 'Gudang Utama Jakarta',
                'address' => 'Jl. Raya Cakung No. 10, Jakarta Timur',
                'description' => 'Gudang utama untuk distribusi pusat.',
                'is_main' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gudang Surabaya',
                'address' => 'Jl. Ahmad Yani No. 50, Surabaya',
                'description' => 'Gudang cabang di Jawa Timur.',
                'is_main' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gudang Bandung',
                'address' => 'Jl. Pasteur No. 23, Bandung',
                'description' => 'Gudang cabang untuk area Jawa Barat.',
                'is_main' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}