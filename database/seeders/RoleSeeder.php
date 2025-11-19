<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::updateOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'pos', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
    }
}
