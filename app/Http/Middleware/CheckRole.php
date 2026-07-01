<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Super Admin always has access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user's role is explicitly allowed
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
