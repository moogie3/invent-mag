<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class SuperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the superuser role if it doesn't exist
        $superUserRole = Role::firstOrCreate(['name' => 'superuser']);

        // Find the first user and assign the superuser role
        // You might want to create a specific user for this or use an existing admin user
        $user = User::first(); // Get the first user, adjust as needed

        if ($user) {
            $user->assignRole($superUserRole);
        }
    }
}
