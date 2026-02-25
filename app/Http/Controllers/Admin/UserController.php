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

        try {
            $this->userService->createUser($request->all());
            return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating user: ' . $e->getMessage())->withInput();
        }
    }

    public function show(User $user)
    {
        $data = $this->userService->getUserEditData($user);
        return response()->json($data);
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

        try {
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
                    ],
                ]);
            }

            return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Error updating user: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Request $request, User $user)
    {
        try {
            $this->userService->deleteUser($user);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
            }

            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()], 500);
            }
            return redirect()->route('admin.users.index')->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }

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