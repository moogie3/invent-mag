<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Log the super admin in as the tenant's first user (impersonation).
     *
     * Strategy:
     * - Find the first (admin) user of the tenant.
     * - Store the super admin's identity and return URL in session.
     * - Log in as that tenant user via the default 'web' guard.
     * - Redirect to the tenant's admin dashboard on their domain.
     *
     * The tenant domain is already the full hostname (e.g. acme.invent-mag.web.id).
     * We redirect to https://{domain}/admin/dashboard so the tenant middleware
     * picks up the correct tenant from the subdomain.
     */
    public function impersonate(Tenant $tenant)
    {
        // Get the tenant's first user (ordered by id ascending = the original owner)
        $tenantUser = $tenant->users()->orderBy('id')->first();

        if (! $tenantUser) {
            return back()->with('error', "Tenant '{$tenant->name}' has no users yet. Cannot impersonate.");
        }

        // Build an encrypted token carrying the impersonation state.
        // Cross-domain session transfer is impossible (different cookies), so we
        // pass the payload as an encrypted URL token.  CaptureImpersonation
        // middleware on the tenant domain decrypts it, sets the session, and
        // logs in the tenant user before stripping the token from the URL.
        $returnUrl = url()->previous() ?: route('superadmin.tenants.index');

        $impersonationToken = encrypt([
            'impersonating_tenant_id'   => $tenant->id,
            'impersonating_tenant_name' => $tenant->name,
            'impersonating_user_id'     => $tenantUser->id,
            'impersonating_user_name'   => $tenantUser->name,
            'superadmin_return_url'     => $returnUrl,
        ]);

        // tenant->domain is the full hostname (e.g. acme.invent-mag.web.id)
        $protocol = request()->isSecure() ? 'https' : 'http';
        $tenantAdminUrl = $protocol . '://' . $tenant->domain . '/admin/dashboard'
            . '?' . http_build_query(['impersonation_token' => $impersonationToken]);

        return redirect()->away($tenantAdminUrl);
    }

    /**
     * Stop impersonating — log out the tenant user and return the super admin
     * to their previous superadmin page.
     */
    public function stopImpersonating(Request $request)
    {
        $returnUrl = session('superadmin_return_url', route('superadmin.tenants.index'));

        // Clear all impersonation session data
        session()->forget([
            'impersonating_tenant_id',
            'impersonating_tenant_name',
            'impersonating_user_name',
            'superadmin_return_url',
        ]);

        // Log out the tenant user from the web guard
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->away($returnUrl)->with('success', 'Impersonation ended. Welcome back.');
    }

    public function toggleActive(Tenant $tenant)
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        return back()->with('success', 'Tenant status updated.');
    }

    public function updateDomain(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'domain' => 'required|string|max:255|unique:tenants,domain,' . $tenant->id,
        ]);

        $tenant->update(['domain' => $data['domain']]);

        return back()->with('success', 'Tenant domain updated successfully.');
    }
}
