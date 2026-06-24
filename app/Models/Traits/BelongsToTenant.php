<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = tenantId();
            }
        });

        static::addGlobalScope('tenant', function (Builder $query) {
            if (tenantId()) {
                $query->where('tenant_id', tenantId());
            }
        });
    }
}
