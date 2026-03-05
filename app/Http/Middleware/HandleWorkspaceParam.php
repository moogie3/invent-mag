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
        // Check for workspace query param
        $workspace = $request->query('workspace');
        
        if ($workspace) {
            // Find tenant
            $tenant = Tenant::where('domain', 'like', strtolower($workspace) . '.%')
                          ->orWhere('name', 'like', '%' . str_replace('-', ' ', $workspace) . '%')
                          ->first();
            
            if ($tenant) {
                // Store tenant ID in session if available
                if ($request->hasSession()) {
                    $request->session()->put('tenant_id', $tenant->id);
                }
                
                // If accessing root with workspace, redirect to admin login
                if ($request->path() === '/') {
                    return redirect('/admin/login?workspace=' . $workspace);
                }
            }
        }
        
        return $next($request);
    }
}
