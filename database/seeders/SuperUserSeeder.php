<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SuperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Super User',
            'email' => 'superuser@example.com',
            'password' => bcrypt('password'),
        ]);

        $role = Role::where('name', 'superuser')->first();

        if ($role) {
            $user->assignRole($role);
        }
    }
}