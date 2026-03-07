<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Traits\UsesTenantModel;

class SetSessionCookieName
{
    use UsesTenantModel;

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant) {
            $domain = $this->extractDomainPart($tenant->domain);
            $cookieName = 'inventmag_' . $domain . '_session';
            config(['session.cookie' => $cookieName]);
        } else {
            config(['session.cookie' => 'inventmag_session']);
        }

        return $next($request);
    }

    private function extractDomainPart(string $fullDomain): string
    {
        $configDomain = config('app.domain', 'localhost');
        $domainWithoutTld = str_replace('.' . $configDomain, '', $fullDomain);
        
        return str_replace('.', '_', $domainWithoutTld);
    }

    private function getCurrentTenant()
    {
        if (method_exists(tenant(), 'get')) {
            return tenant()->get();
        }
        
        $tenantModel = config('multitenancy.tenant_model', 'App\Models\Tenant');
        
        if (class_exists($tenantModel)) {
            $currentTenantId = tenant()->id ?? null;
            
            if ($currentTenantId) {
                return (new $tenantModel())->find($currentTenantId);
            }
        }
        
        return null;
    }
}
