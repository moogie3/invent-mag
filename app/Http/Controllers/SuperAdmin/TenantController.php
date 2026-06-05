<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('plan')->latest()->paginate(20);
        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('plan');
        return view('superadmin.tenants.show', compact('tenant'));
    }

    public function impersonate(Tenant $tenant)
    {
        return back()->with('error', 'Impersonation not yet implemented.');
    }

    public function toggleActive(Tenant $tenant)
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        return back()->with('success', 'Tenant status updated.');
    }
}
