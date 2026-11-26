<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SingleTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        return Tenant::query()->orderBy('id')->first();
    }
}
