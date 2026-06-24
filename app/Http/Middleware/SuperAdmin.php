<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'super_admin') {
            \Illuminate\Support\Facades\DB::statement("SET app.bypass_rls = 'on'");
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}
