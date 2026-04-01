<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the authenticated user is suspended, log out and redirect to login with error.
 */
class EnsureUserNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->suspended_at) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account has been suspended.']);
        }

        return $next($request);
    }
}
