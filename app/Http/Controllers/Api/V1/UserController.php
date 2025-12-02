<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * @responseField data object[] A list of users.
     * @responseField data[].id integer The ID of the user.
     * @responseField data[].name string The name of the user.
     * @responseField data[].shopname string The shop name of the user.
     * @responseField data[].address string The address of the user.
     * @responseField data[].email string The email address of the user.
     * @responseField data[].email_verified_at string The email verification timestamp.
     * @responseField data[].two_factor_secret string The two-factor secret.
     * @responseField data[].two_factor_recovery_codes string The two-factor recovery codes.
     * @responseField data[].two_factor_confirmed_at string The two-factor confirmation timestamp.
     * @responseField data[].remember_token string The remember token.
     * @responseField data[].avatar string The URL or path to the user's avatar.
     * @responseField data[].timezone string The timezone of the user.
     * @responseField data[].system_settings object The system settings for the user.
     * @responseField data[].accounting_settings object The accounting settings for the user.
     * @responseField data[].created_at string The date and time the user was created.
     * @responseField data[].updated_at string The date and time the user was last updated.
     * @responseField data[].roles object[] A list of roles assigned to the user.
     * @responseField data[].permissions object[] A list of permissions assigned to the user.
     * @responseField links object Links for pagination.
     * @responseField links.first string The URL of the first page.
     * @responseField links.last string The URL of the last page.
     * @responseField links.prev string The URL of the previous page.
     * @responseField links.next string The URL of the next page.
     * @responseField meta object Metadata for pagination.
     * @responseField meta.current_page integer The current page number.
     * @responseField meta.from integer The starting number of the results on the current page.
     * @responseField meta.last_page integer The last page number.
     * @responseField meta.path string The URL path.
     * @responseField meta.per_page integer The number of results per page.
     * @responseField meta.to integer The ending number of the results on the current page.
     * @responseField meta.total integer The total number of results.
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
     * @responseField id integer The ID of the user.
     * @responseField name string The name of the user.
     * @responseField shopname string The shop name of the user.
     * @responseField address string The address of the user.
     * @responseField email string The email address of the user.
     * @responseField avatar string The URL or path to the user's avatar.
     * @responseField timezone string The timezone of the user.
     * @responseField system_settings object The system settings for the user.
     * @responseField accounting_settings object The accounting settings for the user.
     * @responseField created_at string The date and time the user was created.
     * @responseField updated_at string The date and time the user was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreUserRequest $request)
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
     * @responseField id integer The ID of the user.
     * @responseField name string The name of the user.
     * @responseField shopname string The shop name of the user.
     * @responseField address string The address of the user.
     * @responseField email string The email address of the user.
     * @responseField email_verified_at string The email verification timestamp.
     * @responseField two_factor_secret string The two-factor secret.
     * @responseField two_factor_recovery_codes string The two-factor recovery codes.
     * @responseField two_factor_confirmed_at string The two-factor confirmation timestamp.
     * @responseField remember_token string The remember token.
     * @responseField avatar string The URL or path to the user's avatar.
     * @responseField timezone string The timezone of the user.
     * @responseField system_settings object The system settings for the user.
     * @responseField accounting_settings object The accounting settings for the user.
     * @responseField created_at string The date and time the user was created.
     * @responseField updated_at string The date and time the user was last updated.
     * @responseField roles object[] A list of roles assigned to the user.
     * @responseField permissions object[] A list of permissions assigned to the user.
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
     * @responseField id integer The ID of the user.
     * @responseField name string The name of the user.
     * @responseField shopname string The shop name of the user.
     * @responseField address string The address of the user.
     * @responseField email string The email address of the user.
     * @responseField avatar string The URL or path to the user's avatar.
     * @responseField timezone string The timezone of the user.
     * @responseField system_settings object The system settings for the user.
     * @responseField accounting_settings object The accounting settings for the user.
     * @responseField created_at string The date and time the user was created.
     * @responseField updated_at string The date and time the user was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateUserRequest $request, User $user)
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
     * @responseField rolePermissions object An object where keys are role names and values are arrays of permission names.
     * @responseField allPermissions array A list of all available permission names.
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