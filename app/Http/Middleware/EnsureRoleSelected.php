<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureRoleSelected
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (int)(Auth::user()->account_type_id ?? 5) === 5) {
            if (!$request->routeIs('role.select') && !$request->routeIs('role.store')) {
                return redirect()->route('role.select');
            }
        }
        return $next($request);
    }
}
