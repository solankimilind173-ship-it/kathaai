<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
                'otp_sent' => $request->session()->get('otp_sent'),
                'pending_email' => $request->session()->get('pending_email'),
            ],
            'onboarding' => function () use ($request) {
                $user = $request->user();

                if (! $user) {
                    return null;
                }

                return [
                    'status' => $user->onboarding_status ?? 'not_started',
                    'last_step' => $user->last_onboarding_step,
                    'demo_project_eligible' => $user->isEligibleForDemoProject(),
                ];
            },
        ];
    }
}
