<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        Supplier::updateOrCreate(
            ['code' => 'SUP001', 'tenant_id' => $tenantId],
            [
                'name' => 'PT. Elektronika Nusantara',
                'address' => 'Jl. Raya Bekasi No. 45, Jakarta Timur',
                'phone_number' => '0218887766',
                'location' => 'IN',
                'payment_terms' => '30',
                'email' => 'elektronika.nusantara@example.com',
                'image' => 'default_placeholder.png',
            ]
        );
        Supplier::updateOrCreate(
            ['code' => 'SUP002', 'tenant_id' => $tenantId],
            [
                'name' => 'CV. Sumber Rejeki',
                'address' => 'Jl. Rajawali No. 12, Bandung',
                'phone_number' => '0227654321',
                'location' => 'IN',
                'payment_terms' => '45',
                'email' => 'sumber.rejeki@example.com',
                'image' => 'default_placeholder.png',
            ]
        );
        Supplier::updateOrCreate(
            ['code' => 'SUP003', 'tenant_id' => $tenantId],
            [
                'name' => 'Tokyo Electronics Ltd.',
                'address' => 'Shinjuku-ku, Tokyo, Japan',
                'phone_number' => '+81-3-1234-5678',
                'location' => 'OUT',
                'payment_terms' => '15',
                'email' => 'tokyo.electronics@example.com',
                'image' => 'default_placeholder.png',
            ]
        );
        Supplier::updateOrCreate(
            ['code' => 'SUP004', 'tenant_id' => $tenantId],
            [
                'name' => 'PT. Surya Mandiri',
                'address' => 'Jl. Ahmad Yani No. 22, Surabaya',
                'phone_number' => '0318889988',
                'location' => 'IN',
                'payment_terms' => '45',
                'email' => 'surya.mandiri@example.com',
                'image' => 'default_placeholder.png',
            ]
        );
        Supplier::updateOrCreate(
            ['code' => 'SUP005', 'tenant_id' => $tenantId],
            [
                'name' => 'Shenzhen Tech Supplies',
                'address' => 'Futian District, Shenzhen, China',
                'phone_number' => '+86-755-1234-5678',
                'location' => 'OUT',
                'payment_terms' => '60',
                'email' => 'shenzhen.tech@example.com',
                'image' => 'default_placeholder.png',
            ]
        );
    }
}