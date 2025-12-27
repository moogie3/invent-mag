<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->insert([
            [
                'name' => 'Walk In Customer',
                'address' => '-',
                'phone_number' => '0',
                'email' => '-',
                'payment_terms' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Budi Santoso',
                'address' => 'Jl. Merdeka No. 45, Jakarta',
                'phone_number' => '081234567890',
                'email' => 'budi.santoso@example.com',
                'payment_terms' => '45',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Siti Aminah',
                'address' => 'Perumahan Griya Asri Blok B2, Bandung',
                'phone_number' => '082112345678',
                'email' => 'siti.aminah@example.com',
                'payment_terms' => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ahmad Fauzi',
                'address' => 'Jl. Diponegoro No. 99, Surabaya',
                'phone_number' => '085312345678',
                'email' => 'ahmad.fauzi@example.com',
                'payment_terms' => '15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lilis Suryani',
                'address' => 'Jl. Gajah Mada No. 12, Yogyakarta',
                'phone_number' => '087812345678',
                'email' => 'lilis.suryani@example.com',
                'payment_terms' => '7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agus Wijaya',
                'address' => 'Jl. Sudirman No. 88, Medan',
                'phone_number' => '089912345678',
                'email' => 'agus.wijaya@example.com',
                'payment_terms' => '45',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}