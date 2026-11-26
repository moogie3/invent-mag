<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

/**
 * Personal standalone: one company, one row. Ignore the request entirely
 * and always resolve the first tenant, so there is no longer a
 * tenant-specific host to route on.
 *
 * The method is named findForRequest (Spatie v4) — writing findTenant here
 * left the class missing its one abstract method, which fatals on class
 * load and 500s every request in the app. The suite cannot catch this:
 * Multitenancy::configureRequests() only runs the finder when
 * ! runningInConsole(), so artisan and PHPUnit never load the class.
 */
class SingleTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        return Tenant::query()->orderBy('id')->first();
    }
}
