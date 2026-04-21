<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant as BaseNeedsTenant;

class NeedsTenantGracefully extends BaseNeedsTenant
{
    /**
     * Handle an incoming request.
     * Override to handle missing tenant gracefully instead of throwing exception.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->tenantFinder->findForRequest($request);
        } catch (NoCurrentTenant $e) {
            // No tenant found - log for debugging but don't throw
            // The HandleTenantFromPath will try as fallback
            \Illuminate\Support\Facades\Log::debug('NoCurrentTenant in NeedsTenantGracefully', [
                'hostname' => $request->getHost(),
            ]);
        }

        return $next($request);
    }
}