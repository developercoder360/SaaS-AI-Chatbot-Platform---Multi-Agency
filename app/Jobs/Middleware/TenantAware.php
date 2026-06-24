<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\DB;

class TenantAware
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     */
    public function handle($job, $next)
    {
        if (property_exists($job, 'tenant_id') && !empty($job->tenant_id)) {
            // Bind to container
            app()->instance('tenant_id', $job->tenant_id);

            // Enforce Postgres RLS
            DB::statement("SET app.current_tenant_id = ?", [$job->tenant_id]);

            // Isolate Cache and Storage
            config([
                'cache.prefix' => config('cache.prefix') . 'tenant_' . $job->tenant_id . ':',
                'filesystems.disks.tenant' => [
                    'driver' => 'local',
                    'root' => storage_path('app/public/tenants/' . $job->tenant_id),
                    'url' => env('APP_URL').'/storage/tenants/' . $job->tenant_id,
                    'visibility' => 'public',
                ]
            ]);
            app('cache')->forgetDriver(config('cache.default'));
        }

        return $next($job);
    }
}
