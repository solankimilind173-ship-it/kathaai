<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'user' => \App\Http\Middleware\EnsureUserIsRegular::class,
            'suspended' => \App\Http\Middleware\EnsureUserNotSuspended::class,
            'password.confirm' => \App\Http\Middleware\EnsurePasswordConfirmed::class,
        ]);

        // Unauthenticated users (e.g. hitting Dashboard or Projects) → login
        $middleware->redirectGuestsTo('/login');

        // Authenticated users visiting login/register → redirect by role in controllers
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\App\Exceptions\InsufficientCreditsException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'required' => $e->required, 'available' => $e->available], 402);
            }

            return redirect()->back()->withErrors(['credits' => $e->getMessage()])->withInput();
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('projects:auto-render')->dailyAt('06:00');
    })
    ->create();
