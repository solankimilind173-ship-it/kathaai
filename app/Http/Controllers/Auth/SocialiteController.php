<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialiteController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('status', 'Could not sign in with ' . $provider . '. Please try again.');
        }

        $user = $this->findOrCreateUser($provider, $socialUser);

        Auth::login($user, true);

        $role = $user->role ?? 'user';
        if (in_array($role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'apple'], true)) {
            abort(404);
        }
    }

    protected function findOrCreateUser(string $provider, SocialiteUser $socialUser): User
    {
        $idColumn = $provider === 'google' ? 'google_id' : 'apple_id';

        $user = User::where($idColumn, $socialUser->getId())->first();

        if ($user) {
            return $user;
        }

        $email = $socialUser->getEmail();
        if ($email !== null && $email !== '') {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update([$idColumn => $socialUser->getId()]);
                return $user;
            }
        }

        $defaultPlan = Plan::where('is_active', true)->orderBy('price')->first();

        return User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? explode('@', $socialUser->getEmail() ?? 'user')[0] ?? 'User',
            'email' => $socialUser->getEmail() ?? $socialUser->getId() . '@' . $provider . '.oauth.local',
            'password' => bcrypt(Str::random(32)),
            $idColumn => $socialUser->getId(),
            'email_verified_at' => now(),
            'plan_id' => $defaultPlan?->id,
            'credits' => 0,
        ]);
    }
}
