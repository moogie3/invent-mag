<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('suppliers')->insert([
            [
                'code' => 'SUP001',
                'name' => 'PT. Elektronika Nusantara',
                'address' => 'Jl. Raya Bekasi No. 45, Jakarta Timur',
                'phone_number' => '0218887766',
                'location' => 'IN',
                'payment_terms' => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SUP002',
                'name' => 'CV. Sumber Rejeki',
                'address' => 'Jl. Rajawali No. 12, Bandung',
                'phone_number' => '0227654321',
                'location' => 'IN',
                'payment_terms' => '45',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SUP003',
                'name' => 'Tokyo Electronics Ltd.',
                'address' => 'Shinjuku-ku, Tokyo, Japan',
                'phone_number' => '+81-3-1234-5678',
                'location' => 'OUT',
                'payment_terms' => '15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SUP004',
                'name' => 'PT. Surya Mandiri',
                'address' => 'Jl. Ahmad Yani No. 22, Surabaya',
                'phone_number' => '0318889988',
                'location' => 'IN',
                'payment_terms' => '45',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SUP005',
                'name' => 'Shenzhen Tech Supplies',
                'address' => 'Futian District, Shenzhen, China',
                'phone_number' => '+86-755-1234-5678',
                'location' => 'OUT',
                'payment_terms' => '60',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}