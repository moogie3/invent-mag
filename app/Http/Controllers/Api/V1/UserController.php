<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @group Users
 *
 * APIs for managing users
 */
class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @queryParam per_page int The number of users to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $users = User::with(['roles', 'permissions'])->paginate($perPage);
        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam shopname string The shop name of the user. Example: John's Shop
     * @bodyParam address string The address of the user. Example: 123 Main St
     * @bodyParam email string required The email address of the user. Example: john.doe@example.com
     * @bodyParam password string required The password for the user. Example: password123
     * @bodyParam avatar string The URL or path to the user's avatar. Example: http://example.com/avatar1.jpg
     * @bodyParam timezone string The timezone of the user. Example: Asia/Jakarta
     * @bodyParam system_settings array System settings for the user. Example: '{"theme": "light"}'
     * @bodyParam accounting_settings array Accounting settings for the user. Example: '{"currency": "IDR"}'
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "name": "John Doe",
     *         "shopname": "John's Shop",
     *         "address": "123 Main St",
     *         "email": "john.doe@example.com",
     *         "avatar": "http://example.com/avatar1.jpg",
     *         "timezone": "Asia/Jakarta",
     *         "system_settings": {"theme": "light"},
     *         "accounting_settings": {"currency": "IDR"},
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shopname' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|string',
            'timezone' => 'nullable|string|max:255',
            'system_settings' => 'nullable|array',
            'accounting_settings' => 'nullable|array',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        return new UserResource($user);
    }

    /**
     * Display the specified user.
     *
     * @urlParam user required The ID of the user. Example: 1
     */
    public function show(User $user)
    {
        return new UserResource($user->load(['roles', 'permissions']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam user integer required The ID of the user. Example: 1
     * @bodyParam name string required The name of the user. Example: Jane Doe
     * @bodyParam shopname string The shop name of the user. Example: Jane's Shop
     * @bodyParam address string The address of the user. Example: 456 Oak Ave
     * @bodyParam email string required The email address of the user. Example: jane.doe@example.com
     * @bodyParam password string The password for the user. Example: newpassword123
     * @bodyParam avatar string The URL or path to the user's avatar. Example: http://example.com/avatar2.jpg
     * @bodyParam timezone string The timezone of the user. Example: America/New_York
     * @bodyParam system_settings array System settings for the user. Example: '{"theme": "dark"}'
     * @bodyParam accounting_settings array Accounting settings for the user. Example: '{"currency": "USD"}'
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "name": "Jane Doe",
     *         "shopname": "Jane's Shop",
     *         "address": "456 Oak Ave",
     *         "email": "jane.doe@example.com",
     *         "avatar": "http://example.com/avatar2.jpg",
     *         "timezone": "America/New_York",
     *         "system_settings": {"theme": "dark"},
     *         "accounting_settings": {"currency": "USD"},
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shopname' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|string',
            'timezone' => 'nullable|string|max:255',
            'system_settings' => 'nullable|array',
            'accounting_settings' => 'nullable|array',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam user integer required The ID of the user to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }

    /**
     * @group Users
     * @title Get All Roles and Permissions
     * @response {
     *  "rolePermissions": {
     *      "admin": ["edit articles", "delete articles"],
     *      "writer": ["edit articles"]
     *  },
     *  "allPermissions": ["edit articles", "delete articles", "publish articles"]
     * }
     */
    public function getRolePermissions()
    {
        $roles = Role::with('permissions')->get();
        $allPermissions = Permission::all()->pluck('name');

        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role->name] = $role->permissions->pluck('name')->toArray();
        }

        return response()->json([
            'rolePermissions' => $rolePermissions,
            'allPermissions' => $allPermissions,
        ]);
    }
}
