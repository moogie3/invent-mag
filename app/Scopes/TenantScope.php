<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (app()->has('currentTenant')) {
            try {
                if ($currentTenant = app('currentTenant')) {
                    $builder->where($model->qualifyColumn('tenant_id'), $currentTenant->id);
                }
            } catch (\Exception $e) {
                // Not bound or resolving error
            }
        }
    }
}