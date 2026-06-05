<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function($perm) {
            $parts = explode('-', $perm->name);
            return $parts[0] ?? 'general';
        });

        $roles = Role::with('permissions')->get();

        return view('superadmin.permissions.index', compact('permissions', 'roles'));
    }
}
