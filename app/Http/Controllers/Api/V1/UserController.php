<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

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
     */
    public function store(Request $request)
    {
        //
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
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
