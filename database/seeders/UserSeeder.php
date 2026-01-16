<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $tenantName = strtolower(str_replace(' ', '-', app('currentTenant')->name));
        $user = User::updateOrCreate(
            ['email' => 'admin-' . $tenantName . '@gmail.com', 'tenant_id' => $tenantId],
            [
                'name' => 'admin-' . $tenantName,
                'password' => Hash::make('password'),
                'avatar' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $role = Role::where('name', 'superuser')->first();
        if ($role) {
            $user->assignRole($role);
        }
    }
}