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
        // Defense-in-depth: check plan user limit before creating
        try {
            $planService = app(PlanService::class);
            if (! $planService->canAddUser()) {
                $limit = $planService->getUserLimit();
                throw new \App\Exceptions\PlanLimitExceededException(
                    "You have reached the maximum number of users ({$limit}) allowed on your current plan. Please upgrade to add more users."
                );
            }
        } catch (\App\Exceptions\PlanLimitExceededException $e) {
            throw $e;
        } catch (\Exception $e) {
            // If PlanService is unavailable (e.g., plans table doesn't exist yet),
            // allow user creation to proceed (backward compatibility)
        }

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        $user->password = $data['password'];
        $user->save();

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
            $user->password = $data['password'];
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
