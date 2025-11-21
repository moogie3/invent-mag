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
     * @queryParam per_page int The number of roles to return per page. Defaults to 15. Example: 25
     *
     * @apiResourceCollection App\Http\Resources\RoleResource
     * @apiResourceModel App\Models\Role
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
     * @bodyParam name string required The name of the role. Example: Admin
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "name": "Admin",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        $role = Role::create($validated);

        return new RoleResource($role);
    }

    /**
     * Display the specified role.
     *
     * @urlParam role required The ID of the role. Example: 1
     *
     * @apiResource App\Http\Resources\RoleResource
     * @apiResourceModel App\Models\Role
     */
    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam role integer required The ID of the role. Example: 1
     * @bodyParam name string required The name of the role. Example: Editor
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "name": "Editor",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $role->update($validated);

        return new RoleResource($role);
    }

    /**
     * Remove the specified resource from storage.
     *
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
