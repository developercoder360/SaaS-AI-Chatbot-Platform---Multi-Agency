<?php

if (!function_exists('tenant')) {
    function tenant(): \App\Models\Tenant
    {
        return app('tenant');
    }
}

if (!function_exists('tenantId')) {
    function tenantId(): ?string
    {
        return app()->bound('tenant_id') ? app('tenant_id') : null;
    }
}
