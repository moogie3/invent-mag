<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SingleTenantFinder extends TenantFinder
{
    public function findTenant(Request $request): ?Tenant
    {
        return Tenant::query()->orderBy('id')->first();
    }
}
