<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        Customer::updateOrCreate(
            ['email' => 'walk-in@' . $tenantId . '.com', 'tenant_id' => $tenantId],
            [
                'name' => 'Walk In Customer',
                'address' => '-',
                'phone_number' => '0',
                'payment_terms' => '0',
            ]
        );
        Customer::updateOrCreate(
            ['email' => 'budi.santoso@example.com', 'tenant_id' => $tenantId],
            [
                'name' => 'Budi Santoso',
                'address' => 'Jl. Merdeka No. 45, Jakarta',
                'phone_number' => '081234567890',
                'payment_terms' => '45',
            ]
        );
        Customer::updateOrCreate(
            ['email' => 'siti.aminah@example.com', 'tenant_id' => $tenantId],
            [
                'name' => 'Siti Aminah',
                'address' => 'Perumahan Griya Asri Blok B2, Bandung',
                'phone_number' => '082112345678',
                'payment_terms' => '30',
            ]
        );
        Customer::updateOrCreate(
            ['email' => 'ahmad.fauzi@example.com', 'tenant_id' => $tenantId],
            [
                'name' => 'Ahmad Fauzi',
                'address' => 'Jl. Diponegoro No. 99, Surabaya',
                'phone_number' => '085312345678',
                'payment_terms' => '15',
            ]
        );
        Customer::updateOrCreate(
            ['email' => 'lilis.suryani@example.com', 'tenant_id' => $tenantId],
            [
                'name' => 'Lilis Suryani',
                'address' => 'Jl. Gajah Mada No. 12, Yogyakarta',
                'phone_number' => '087812345678',
                'payment_terms' => '7',
            ]
        );
        Customer::updateOrCreate(
            ['email' => 'agus.wijaya@example.com', 'tenant_id' => $tenantId],
            [
                'name' => 'Agus Wijaya',
                'address' => 'Jl. Sudirman No. 88, Medan',
                'phone_number' => '089912345678',
                'payment_terms' => '45',
            ]
        );
    }
}