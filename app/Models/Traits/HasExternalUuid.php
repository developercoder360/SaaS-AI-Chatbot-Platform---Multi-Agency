<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait HasExternalUuid
{
    /**
     * Boot the trait to automatically generate a UUID.
     */
    protected static function bootHasExternalUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Use the UUID column for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
