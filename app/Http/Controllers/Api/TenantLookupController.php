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

        $tenant = Tenant::where('name', $request->shopname)->first();

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
