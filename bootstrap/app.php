<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        $renderInertiaError = function (Request $request, int $status, string $message) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $status);
            }

            if (! $request->isMethod('GET')) {
                return redirect()->back()->with('error', $message)->withInput();
            }

            return Inertia::render('Error', [
                'status' => $status,
                'message' => $message,
            ])->toResponse($request)->setStatusCode($status);
        };

        $exceptions->renderable(function (HttpExceptionInterface $e, Request $request) use ($renderInertiaError) {
            $status = $e->getStatusCode();

            if (! in_array($status, [403, 404, 419, 422], true)) {
                return null;
            }

            $message = $e->getMessage() ?: Response::$statusTexts[$status] ?? 'Unexpected error.';

            return $renderInertiaError($request, $status, $message);
        });

        $exceptions->renderable(function (Throwable $e, Request $request) use ($renderInertiaError) {
            if (app()->environment(['local', 'testing'])) {
                return null;
            }

            report($e);

            return $renderInertiaError($request, 500, 'Something unexpected happened while handling your request.');
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('projects:auto-render')->dailyAt('06:00');
    })
    ->create();
