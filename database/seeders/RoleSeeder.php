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
        Role::create(['name' => 'superuser']);
        Role::create(['name' => 'staff']);
        Role::create(['name' => 'pos']);
        Role::create(['name' => 'accountant']);
    }
}
