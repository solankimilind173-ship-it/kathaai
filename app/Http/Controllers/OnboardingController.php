<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return back();
        }

        if ($user->onboarding_status !== 'completed') {
            $user->forceFill([
                'onboarding_status' => 'in_progress',
            ])->save();
        }

        return back();
    }

    public function step(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return back();
        }

        $data = $request->validate([
            'step' => ['nullable', 'string', 'max:191'],
        ]);

        $user->forceFill([
            'onboarding_status' => $user->onboarding_status === 'completed' ? $user->onboarding_status : 'in_progress',
            'last_onboarding_step' => $data['step'] ?? $user->last_onboarding_step,
        ])->save();

        return back();
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return back();
        }

        $data = $request->validate([
            'step' => ['nullable', 'string', 'max:191'],
        ]);

        $user->forceFill([
            'onboarding_status' => 'completed',
            'last_onboarding_step' => $data['step'] ?? $user->last_onboarding_step,
        ])->save();

        return back();
    }

    public function skip(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return back();
        }

        $user->forceFill([
            'onboarding_status' => 'completed',
            'last_onboarding_step' => $user->last_onboarding_step ?? 'skipped',
        ])->save();

        return back();
    }
}

