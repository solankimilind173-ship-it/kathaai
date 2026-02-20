<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = $request->session()->get('auth.password_confirmed_at');
        $timeout = config('auth.password_timeout', 10800); // 3 hours

        if ($confirmedAt && (time() - $confirmedAt) < $timeout) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('password.confirm');
    }
}
