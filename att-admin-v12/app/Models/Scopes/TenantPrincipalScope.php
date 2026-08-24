<?php

namespace App\Models\Scopes;

use App\Models\Principal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantPrincipalScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('current_tenant_principal_ids')) {
            $tenantIds = app('current_tenant_principal_ids');
            if (is_array($tenantIds) && !empty($tenantIds)) {
                $builder->whereIn($model->qualifyColumn('principal_id'), $tenantIds);
            }
        } elseif (app()->bound('current_tenant_principal')) {
            $tenant = app('current_tenant_principal');
            if ($tenant instanceof Principal) {
                $builder->where($model->qualifyColumn('principal_id'), $tenant->id);
            }
        }
    }
}
