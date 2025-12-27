<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserService
{
    public function getUserIndexData()
    {
        $users = User::all();
        $roles = Role::all();
        $permissions = Permission::all();
        return compact('users', 'roles', 'permissions');
    }

    public function getUserEditData(User $user)
    {
        $roles = Role::all();
        $permissions = Permission::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        return compact('user', 'roles', 'permissions', 'userRoles', 'userPermissions');
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        if (isset($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->save();
        }

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        } else {
            $user->syncRoles([]);
        }

        if (isset($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        } else {
            $user->syncPermissions([]);
        }

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}
