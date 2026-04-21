<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;

class DebugTenantResolution
{
    public function handle(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();
        $path = $request->path();
        
        // Log the request info for debugging
        \Illuminate\Support\Facades\Log::info('Tenant Resolution Debug', [
            'hostname' => $hostname,
            'path' => $path,
            'full_url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        try {
            return $next($request);
        } catch (NoCurrentTenant $e) {
            \Illuminate\Support\Facades\Log::error('NoCurrentTenant Exception', [
                'hostname' => $hostname,
                'message' => $e->getMessage(),
            ]);
            
            // Redirect to frontend
            $frontendUrl = config('app.frontend_url');
            return redirect()->away($frontendUrl . '/login');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tenant Resolution Failed', [
                'hostname' => $hostname,
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
}
