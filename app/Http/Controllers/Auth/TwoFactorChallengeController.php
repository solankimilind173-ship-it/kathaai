<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityEventService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get('two_factor.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }
        $user = User::find($userId);
        if (! $user) {
            $request->session()->forget('two_factor.user_id');

            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request, TwoFactorService $twoFactor, SecurityEventService $securityEvent): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $userId = $request->session()->get('two_factor.user_id');
        if (! $userId) {
            throw ValidationException::withMessages(['code' => ['Session expired. Please log in again.']]);
        }

        $user = User::find($userId);
        if (! $user || ! $twoFactor->confirmTwoFactor($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => ['The provided code was invalid.']]);
        }

        $request->session()->forget('two_factor.user_id');
        Auth::login($user, $request->session()->get('two_factor.remember', false));
        $request->session()->regenerate();

        $securityEvent->log($user, SecurityEventService::LOGIN, $request);

        $role = $user->role ?? 'user';
        if (in_array($role, ['admin', 'super_admin'], true)) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
