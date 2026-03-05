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
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        
        // Check if the first path segment matches a tenant domain (without TLD)
        // Example: demo-starter, demo-pro, my-shop
        $segments = explode('/', $path);
        
        if (!empty($segments[0])) {
            $tenantSlug = $segments[0];
            
            // Check if this slug matches any tenant's domain prefix
            // We look for tenants where domain starts with this slug
            $tenant = Tenant::where('domain', 'like', $tenantSlug . '.%')
                           ->orWhere('name', 'like', '%' . $tenantSlug . '%')
                           ->first();
            
            if ($tenant) {
                // Make this tenant current
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
