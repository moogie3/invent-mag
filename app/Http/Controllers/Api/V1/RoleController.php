<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRoleRequest;
use App\Http\Requests\Api\V1\UpdateRoleRequest;
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
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Admin","guard_name":"web","permissions":["view-users","edit-users"]},...],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 201 scenario="Success" {"data":{"id":1,"name":"Admin","guard_name":"web",...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreRoleRequest $request)
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Admin","guard_name":"web","permissions":["view-users","edit-users"]}}
     * @response 404 scenario="Not Found" {"message": "Role not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Editor","guard_name":"web",...}}
     * @response 404 scenario="Not Found" {"message": "Role not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateRoleRequest $request, Role $role)
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
     * @response 404 scenario="Not Found" {"message": "Role not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->noContent();
    }
}