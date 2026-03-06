<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantLookupController extends Controller
{
    /**
     * Find a tenant's domain by their shop name.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'shopname' => 'required|string|max:255',
        ]);

        $shopname = strtolower($request->shopname);
        
        // First try matching by domain prefix (e.g., "demo-starter" matches "demo-starter.invent-mag.web.id")
        $tenant = Tenant::where('domain', 'like', $shopname . '.%')->first();
        
        // If not found, try matching by name (MySQL LIKE is case-insensitive)
        if (!$tenant) {
            $tenant = Tenant::where('name', 'like', '%' . str_replace('-', ' ', $shopname) . '%')->first();
        }
        
        // Last resort broad search
        if (!$tenant) {
            $tenant = Tenant::where('name', 'like', '%' . $shopname . '%')->first();
        }

        if ($tenant && $tenant->domain) {
            // Return workspace slug instead of full domain (subdomains don't have SSL)
            $workspaceSlug = explode('.', $tenant->domain)[0];
            return response()->json([
                'tenant_domain' => $tenant->domain,
                'workspace_slug' => $workspaceSlug,
            ]);
        }

        return response()->json([
            'message' => 'Shop not found.',
        ], 404);
    }
}
