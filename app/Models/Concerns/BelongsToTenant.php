<?php

namespace App\Models\Concerns;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            if (! $model->tenant_id) {
                if ($currentTenant = app('currentTenant')) {
                    $model->tenant_id = $currentTenant->id;
                }
            }
        });
    }

    public function scopeWithoutTenancy(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope(TenantScope::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
