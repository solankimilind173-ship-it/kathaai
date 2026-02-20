<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict user-area routes to non-admin roles. Admins and super_admins are redirected to the admin dashboard.
 * Use on routes like /dashboard, /projects, /profile, /upgrade, /gallery, etc.
 */
class EnsureUserIsRegular
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $role = $request->user()->role ?? 'user';
        if (in_array($role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
