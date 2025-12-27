<?php

namespace App\Actions\Fortify;



use App\Models\User;

use App\Models\Tenant;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;

use Illuminate\Validation\Rule;

use Laravel\Fortify\Contracts\CreatesNewUsers;

use Spatie\Permission\Models\Role;

use Illuminate\Support\Str;



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

            'shopname' => ['required', 'string', 'max:255', Rule::unique('tenants', 'name')],

            'email' => [

                'required',

                'string',

                'email',

                'max:255',

                Rule::unique(User::class),

            ],

            'password' => $this->passwordRules(),

        ])->validate();



        $tenant = Tenant::create([

            'name' => $input['shopname'],

            'domain' => Str::slug($input['shopname']) . '.' . config('app.domain'),

        ]);



        $user = $tenant->run(function () use ($input) {

            return User::create([

                'name' => $input['name'],

                'email' => $input['email'],

                'password' => Hash::make($input['password']),

                'shopname' => $input['shopname'] ?? null,

                'address' => $input['address'] ?? null,

                'avatar' => $input['avatar'] ?? null,

                'timezone' => $input['timezone'] ?? null,

            ]);

        });

        

        // Assign a default role (e.g., 'superuser') to the new user

        $superuserRole = Role::firstOrCreate(['name' => 'superuser']);

        $user->assignRole($superuserRole);



        return $user;

    }

}