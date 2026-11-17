<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user && $user->is_active && $user->isRole(...$roles), 403, 'Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
