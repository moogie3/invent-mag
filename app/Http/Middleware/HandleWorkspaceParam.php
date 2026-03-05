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
     * This allows /?workspace=demo-starter to work without subdomain SSL.
     * Also handles session-based tenant persistence after login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // First check if tenant is already set in session (after login)
        $tenantId = $request->session()->get('tenant_id');
        
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $tenant->makeCurrent();
                return $next($request);
            }
        }
        
        // Check for workspace query param
        $workspace = $request->query('workspace');
        
        if ($workspace) {
            // Look up tenant by workspace slug
            $tenant = Tenant::where('domain', 'like', $workspace . '.%')
                          ->orWhere('name', 'like', '%' . str_replace('-', ' ', $workspace) . '%')
                          ->first();
            
            if ($tenant) {
                // Make this tenant current
                $tenant->makeCurrent();
                
                // Store tenant ID in session for subsequent requests
                $request->session()->put('tenant_id', $tenant->id);
                
                // If accessing root with workspace, redirect to admin login
                if ($request->path() === '/') {
                    // Clear workspace from URL and go to /admin/login
                    $request->merge([]); // Clear query params
                    $request->server->set('REQUEST_URI', '/admin/login');
                    $request->server->set('PATH_INFO', '/admin/login');
                }
            }
        }
        
        return $next($request);
    }
}
