<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    // app/Http/Middleware/RoleMiddleware.php
    public function handle($request, \Closure $next, string $role)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user)
            return redirect()->route('login');

        // thử lấy từ nhiều nơi, mặc định '(NONE)'
        $have = strtoupper($user->type->code ?? '(NONE)');  // <-- dùng type()
        $need = strtoupper($role);

        if ($have !== $need) {
            abort(403, "Tài khoản của bạn không phù hợp");
        }

        return $next($request);
    }

}
