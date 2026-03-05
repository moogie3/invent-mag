<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class WorkspaceTenantFinder extends TenantFinder
{
    use UsesMultitenancyConfig;

    public function findForRequest(Request $request): ?IsTenant
    {
        // 1. Check for workspace query parameter (priority)
        $workspace = $request->query('workspace');
        if ($workspace) {
            $tenant = app(IsTenant::class)::where('domain', 'like', strtolower($workspace) . '.%')
                        ->orWhere('name', 'like', '%' . str_replace('-', ' ', $workspace) . '%')
                        ->first();
            
            if ($tenant) {
                return $tenant;
            }
        }

        // 2. Check session
        if ($request->hasSession()) {
            $tenantId = $request->session()->get('tenant_id');
            if ($tenantId) {
                return app(IsTenant::class)::find($tenantId);
            }
        }

        // 3. Fallback to domain (subdomains)
        $host = $request->getHost();
        return app(IsTenant::class)::whereDomain($host)->first();
    }
}
