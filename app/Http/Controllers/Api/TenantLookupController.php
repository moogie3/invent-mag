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

        $shopname = $request->shopname;
        
        // First try exact name match
        $tenant = Tenant::where('name', $shopname)->first();
        
        // If not found, try matching by domain prefix (e.g., "demo-starter" matches "demo-starter.invent-mag.up.railway.app")
        if (!$tenant) {
            $tenant = Tenant::where('domain', 'like', $shopname . '.%')->first();
        }
        
        // If still not found, try matching by name containing the shopname (case insensitive)
        if (!$tenant) {
            $tenant = Tenant::where('name', 'ilike', '%' . $shopname . '%')->first();
        }

        if ($tenant && $tenant->domain) {
            return response()->json([
                'tenant_domain' => $tenant->domain,
            ]);
        }

        return response()->json([
            'message' => 'Shop not found.',
        ], 404);
    }
}
