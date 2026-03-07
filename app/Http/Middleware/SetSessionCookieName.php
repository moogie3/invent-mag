<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSessionCookieName
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->getCurrentTenant($request);

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

    private function getCurrentTenant($request)
    {
        $hostname = $request->getHost();
        
        if ($hostname && $hostname !== config('app.domain', 'localhost')) {
            $tenantModel = config('multitenancy.tenant_model', 'App\Models\Tenant');
            
            if (class_exists($tenantModel)) {
                return (new $tenantModel())->where('domain', $hostname)->first();
            }
        }
        
        return null;
    }
}
