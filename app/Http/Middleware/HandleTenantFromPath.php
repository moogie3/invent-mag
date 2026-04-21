<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class HandleTenantFromPath
{
    /**
     * Handle an incoming request.
     * Supports tenant identification via path: /{tenant-slug}/admin/...
     * Also handles subdomain fallback when NeedsTenant fails.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tenant is already set (subdomain-based resolution worked)
        if (app()->has('currentTenant') && app('currentTenant')) {
            return $next($request);
        }
        
        // Get hostname from request (for subdomain detection)
        $hostname = $request->getHost();
        
        // Try to extract tenant from hostname (subdomain)
        // e.g., demo-enterprise.invent-mag.web.id -> demo-enterprise
        $appDomain = config('app.domain') ?: 'invent-mag.web.id';
        
        // If app domain is localhost, skip subdomain extraction
        // Otherwise extract subdomain
        if ($appDomain !== 'localhost' && str_ends_with($hostname, $appDomain)) {
            $subdomain = str_replace('.' . $appDomain, '', $hostname);
            
            // Try to find tenant by subdomain
            $tenant = Tenant::where('domain', 'like', $subdomain . '.%')
                           ->first();
            
            if ($tenant) {
                \Illuminate\Support\Facades\Log::debug('HandleTenantFromPath: Found tenant via hostname', [
                    'hostname' => $hostname,
                    'tenant' => $tenant->name,
                ]);
                
                $tenant->makeCurrent();
                return $next($request);
            }
        }
        
        // Fallback: Check if the first path segment matches a tenant domain prefix
        $path = $request->path();
        $segments = explode('/', $path);
        
        if (!empty($segments[0])) {
            $tenantSlug = $segments[0];
            
            // Check if this slug matches any tenant's domain prefix
            $tenant = Tenant::where('domain', 'like', $tenantSlug . '.%')
                           ->orWhere('name', 'like', '%' . $tenantSlug . '%')
                           ->first();
            
            if ($tenant) {
                \Illuminate\Support\Facades\Log::debug('HandleTenantFromPath: Found tenant via path', [
                    'path' => $path,
                    'tenant' => $tenant->name,
                ]);
                
                $tenant->makeCurrent();
                
                // Remove the tenant slug from the path for route matching
                // /demo-starter/admin/login -> /admin/login
                $newPath = implode('/', array_slice($segments, 1));
                $request->server->set('REQUEST_URI', '/' . $newPath);
                $request->server->set('PATH_INFO', '/' . $newPath);
            }
        }
        
        return $next($request);
    }
}
