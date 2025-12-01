<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

/**
 * @group Roles
 *
 * APIs for managing roles
 */
class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     *
     * @group Roles
     * @authenticated
     * @queryParam per_page int The number of roles to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of roles.
     * @responseField data[].id integer The ID of the role.
     * @responseField data[].name string The name of the role.
     * @responseField data[].guard_name string The guard name for the role.
     * @responseField data[].created_at string The date and time the role was created.
     * @responseField data[].updated_at string The date and time the role was last updated.
     * @responseField data[].permissions array A list of permissions assigned to the role.
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
        $perPage = $request->query('per_page', 15);
        $roles = Role::with('permissions')->paginate($perPage);
        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Roles
     * @authenticated
     * @bodyParam name string required The name of the role. Example: Admin
     *
     * @responseField id integer The ID of the role.
     * @responseField name string The name of the role.
     * @responseField guard_name string The guard name for the role.
     * @responseField created_at string The date and time the role was created.
     * @responseField updated_at string The date and time the role was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreRoleRequest $request)
    {
        $validated = $request->validated();
        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Display the specified role.
     *
     * @group Roles
     * @authenticated
     * @urlParam role required The ID of the role. Example: 1
     *
     * @responseField id integer The ID of the role.
     * @responseField name string The name of the role.
     * @responseField guard_name string The guard name for the role.
     * @responseField created_at string The date and time the role was created.
     * @responseField updated_at string The date and time the role was last updated.
     * @responseField permissions array A list of permissions assigned to the role.
     */
    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Roles
     * @authenticated
     * @urlParam role integer required The ID of the role. Example: 1
     * @bodyParam name string required The name of the role. Example: Editor
     *
     * @responseField id integer The ID of the role.
     * @responseField name string The name of the role.
     * @responseField guard_name string The guard name for the role.
     * @responseField created_at string The date and time the role was created.
     * @responseField updated_at string The date and time the role was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();
        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Roles
     * @authenticated
     * @urlParam role integer required The ID of the role to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->noContent();
    }
}