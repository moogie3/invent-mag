<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Contracts\IsTenant as IsTenantContract;
use Spatie\Multitenancy\Models\Concerns\IsTenant;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Multitenancy\TenantCollection;

class Tenant extends Model implements IsTenantContract
{
    use IsTenant, UsesTenantConnection;

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }
}
