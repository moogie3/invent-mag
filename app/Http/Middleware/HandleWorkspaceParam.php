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
        $workspace = $request->query('workspace');
        
        // 1. Identify by Query Parameter
        if ($workspace) {
            $tenant = Tenant::where('domain', 'like', strtolower($workspace) . '.%')
                          ->orWhere('name', 'like', '%' . str_replace('-', ' ', $workspace) . '%')
                          ->first();
            
            if ($tenant) {
                $tenant->makeCurrent();
                if ($request->hasSession()) {
                    $request->session()->put('tenant_id', $tenant->id);
                }
                
                if ($request->path() === '/') {
                    return redirect('/admin/login?workspace=' . $workspace);
                }
            }
        } 
        // 2. Identify by Session (for dashboard and other routes)
        else if ($request->hasSession() && $tenantId = $request->session()->get('tenant_id')) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }
        
        return $next($request);
    }
}
