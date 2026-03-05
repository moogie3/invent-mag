<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class HandleWorkspaceParam
{
    /**
     * Handle workspace query param BEFORE tenant middleware runs.
     * This allows /?workspace=demo-starter to redirect to tenant login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->query('workspace');
        
        if ($workspace && $request->path() === '/') {
            // Look up tenant by workspace slug
            $tenant = Tenant::where('domain', 'like', $workspace . '.%')
                          ->orWhere('name', 'like', '%' . str_replace('-', ' ', $workspace) . '%')
                          ->first();
            
            if ($tenant) {
                // Make this tenant current - this allows the tenant's routes to work
                // without needing subdomain SSL
                $tenant->makeCurrent();
                
                // Update request to go to /admin/login for this tenant
                $request->merge([]); // Clear workspace from query
                $request->server->set('REQUEST_URI', '/admin/login');
                $request->server->set('PATH_INFO', '/admin/login');
            }
        }
        
        return $next($request);
    }
}
