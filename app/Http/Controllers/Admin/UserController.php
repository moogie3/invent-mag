<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware('permission:view-users', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-users', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-users', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-users', ['only' => ['destroy']]);
        $this->userService = $userService;
    }

    public function index()
    {
        $data = $this->userService->getUserIndexData();
        return view('admin.users.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'permissions' => 'nullable|array',
        ]);

        $this->userService->createUser($request->all());

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(User $user)
    {
        $data = $this->userService->getUserEditData($user);

        if (request()->ajax()) {
            return response()->json($data);
        }

        return view('admin.users.edit', $data);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'permissions' => 'nullable|array',
        ]);

        $this->userService->updateUser($user, $request->all());

        if (request()->ajax()) {
            $user->load('roles', 'permissions');
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ]
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->userService->deleteUser($user);
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
