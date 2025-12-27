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
        Customer::create([
            'name' => 'Walk In Customer',
            'address' => '-',
            'phone_number' => '0',
            'email' => '-',
            'payment_terms' => '0',
        ]);
        Customer::create([
            'name' => 'Budi Santoso',
            'address' => 'Jl. Merdeka No. 45, Jakarta',
            'phone_number' => '081234567890',
            'email' => 'budi.santoso@example.com',
            'payment_terms' => '45',
        ]);
        Customer::create([
            'name' => 'Siti Aminah',
            'address' => 'Perumahan Griya Asri Blok B2, Bandung',
            'phone_number' => '082112345678',
            'email' => 'siti.aminah@example.com',
            'payment_terms' => '30',
        ]);
        Customer::create([
            'name' => 'Ahmad Fauzi',
            'address' => 'Jl. Diponegoro No. 99, Surabaya',
            'phone_number' => '085312345678',
            'email' => 'ahmad.fauzi@example.com',
            'payment_terms' => '15',
        ]);
        Customer::create([
            'name' => 'Lilis Suryani',
            'address' => 'Jl. Gajah Mada No. 12, Yogyakarta',
            'phone_number' => '087812345678',
            'email' => 'lilis.suryani@example.com',
            'payment_terms' => '7',
        ]);
        Customer::create([
            'name' => 'Agus Wijaya',
            'address' => 'Jl. Sudirman No. 88, Medan',
            'phone_number' => '089912345678',
            'email' => 'agus.wijaya@example.com',
            'payment_terms' => '45',
        ]);
    }
}