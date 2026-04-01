<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to admin and super_admin roles. Used for /admin routes.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $role = $request->user()->role ?? 'user';
        if (! in_array($role, ['admin', 'super_admin'], true)) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
