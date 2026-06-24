<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Try custom domain first
        $tenant = Tenant::where('custom_domain', $host)->first();

        // Fallback to subdomain
        if (! $tenant) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::where('slug', $subdomain)->first();
        }

        if (! $tenant) {
            abort(404, 'Agency not found.');
        }

        if ($tenant->status === 'suspended') {
            abort(403, 'This account is suspended.');
        }

        // Authorization check: ensure the logged-in user belongs to this tenant.
        if (Auth::check() && Auth::user()->tenant_id !== $tenant->id && Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access to this agency.');
        }

        // Bind to container — available throughout the request lifecycle
        app()->instance('tenant', $tenant);
        app()->instance('tenant_id', $tenant->id);

        // Enforce Row-Level Security in the database engine
        \Illuminate\Support\Facades\DB::statement("SET app.current_tenant_id = ?", [$tenant->id]);

        // Isolate Cache and Storage
        config([
            'cache.prefix' => config('cache.prefix') . 'tenant_' . $tenant->id . ':',
            'filesystems.disks.tenant' => [
                'driver' => 'local',
                'root' => storage_path('app/public/tenants/' . $tenant->id),
                'url' => env('APP_URL').'/storage/tenants/' . $tenant->id,
                'visibility' => 'public',
            ]
        ]);
        app('cache')->forgetDriver(config('cache.default'));

        return $next($request);
    }
}
