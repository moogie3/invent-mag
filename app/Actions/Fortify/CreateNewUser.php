<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            // Add other fields if they are part of your registration form and fillable
            'shopname' => $input['shopname'] ?? null,
            'address' => $input['address'] ?? null,
            'avatar' => $input['avatar'] ?? null,
            'timezone' => $input['timezone'] ?? null,
        ]);

        // Assign a default role (e.g., 'staff') to the new user
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $user->assignRole($staffRole);

        return $user;
    }
}