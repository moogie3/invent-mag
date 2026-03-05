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
        
        // 1. Priority: Identify by Query Parameter (URL always wins)
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
        // 2. Secondary: Identify by Authenticated User (The ultimate safety net)
        else if (auth()->check() && auth()->user()->tenant_id) {
            $tenant = Tenant::find(auth()->user()->tenant_id);
            if ($tenant) {
                $tenant->makeCurrent();
                if ($request->hasSession()) {
                    $request->session()->put('tenant_id', $tenant->id);
                }
            }
        }
        // 3. Fallback: Identify by Session
        else if ($request->hasSession() && $tenantId = $request->session()->get('tenant_id')) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }
        
        return $next($request);
    }
}
