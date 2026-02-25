<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
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
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the users.
     *
     * @group Users
     * @authenticated
     * @queryParam per_page int The number of users to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Admin User","email":"admin@example.com",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $data = $this->userService->getUserIndexData();
        return UserResource::collection($data['users']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Users
     * @authenticated
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam shopname string The shop name of the user. Example: John's Shop
     * @bodyParam address string The address of the user. Example: 123 Main St
     * @bodyParam email string required The email address of the user. Example: john.doe@example.com
     * @bodyParam password string required The password for the user. Example: password123
     * @bodyParam password confirmation string required The password for the user. Example: password123
     * @bodyParam avatar string The URL or path to the user's avatar. Example: http://example.com/avatar1.jpg
     * @bodyParam timezone string The timezone of the user. Example: Asia/Jakarta
     * @bodyParam system_settings array System settings for the user.
     * @bodyParam accounting_settings array Accounting settings for the user.
     *
     * @response 201 scenario="Success" {"data":{"id":1,"name":"John Doe","email":"john.doe@example.com",...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());
        return new UserResource($user);
    }

    /**
     * Display the specified user.
     *
     * @group Users
     * @authenticated
     * @urlParam user required The ID of the user. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Admin User","email":"admin@example.com",...}}
     * @response 404 scenario="Not Found" {"message": "User not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(User $user)
    {
        return new UserResource($user->load(['roles', 'permissions']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Users
     * @authenticated
     * @urlParam user integer required The ID of the user. Example: 1
     * @bodyParam name string required The name of the user. Example: Jane Doe
     * @bodyParam shopname string The shop name of the user. Example: Jane's Shop
     * @bodyParam address string The address of the user. Example: 456 Oak Ave
     * @bodyParam email string required The email address of the user. Example: jane.doe@example.com
     * @bodyParam password string The password for the user. Example: newpassword123
     * @bodyParam password confirmationstring The password for the user. Example: newpassword123
     * @bodyParam avatar string The URL or path to the user's avatar. Example: http://example.com/avatar2.jpg
     * @bodyParam timezone string The timezone of the user. Example: America/New_York
     * @bodyParam system_settings array System settings for the user.
     * @bodyParam accounting_settings array Accounting settings for the user.
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Jane Doe","email":"jane.doe@example.com",...}}
     * @response 404 scenario="Not Found" {"message": "User not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->userService->updateUser($user, $request->validated());
        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Users
     * @authenticated
     * @urlParam user integer required The ID of the user to delete. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "User not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(User $user)
    {
        $this->userService->deleteUser($user);
        return response()->noContent();
    }

    /**
     * Get All Roles and Permissions
     *
     * @group Users
     * @authenticated
     *
     * @response 200 scenario="Success" {"rolePermissions":{"admin":["view-users","create-users"],"editor":["edit-posts"]},"allPermissions":["view-users","create-users","edit-posts"]}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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