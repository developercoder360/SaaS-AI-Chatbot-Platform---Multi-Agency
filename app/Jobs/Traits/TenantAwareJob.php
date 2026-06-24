<?php

namespace App\Jobs\Traits;

use App\Jobs\Middleware\TenantAware;

trait TenantAwareJob
{
    public ?string $tenant_id = null;

    /**
     * Capture the active tenant when the job is dispatched.
     */
    public function captureTenant()
    {
        if (app()->bound('tenant_id')) {
            $this->tenant_id = app('tenant_id');
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new TenantAware];
    }
}
