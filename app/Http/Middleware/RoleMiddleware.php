<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    // app/Http/Middleware/RoleMiddleware.php
    public function handle($request, Closure $next, string $roles)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Lấy mã role thật của user (ví dụ CLIENT, BUSS, ADMIN...)
        $have = strtoupper($user->type->code ?? '(NONE)');

        // Hỗ trợ nhiều role, ngăn cách bởi | hoặc ,
        $rolesArray = preg_split('/[|,]/', strtoupper($roles));

        if (!in_array($have, $rolesArray, true)) {
            abort(403, "Tài khoản của bạn không phù hợp với quyền truy cập.");
        }

        return $next($request);
    }
}
