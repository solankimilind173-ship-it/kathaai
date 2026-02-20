<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtp;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    private const OTP_CACHE_PREFIX = 'registration_otp:';
    private const OTP_TTL_SECONDS = 600; // 10 minutes

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Send 6-digit OTP to email and store pending registration in session.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $otp = (string) random_int(100000, 999999);
        Cache::put(self::OTP_CACHE_PREFIX . $validated['email'], $otp, self::OTP_TTL_SECONDS);

        $request->session()->put('pending_registration', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Mail::to($validated['email'])->send(new RegistrationOtp(
            $validated['email'],
            $otp,
            $validated['name']
        ));

        return back()->with('otp_sent', true)->with('pending_email', $validated['email']);
    }

    /**
     * Resend OTP for pending registration (same session).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('pending_registration');
        if (!$pending || empty($pending['email'])) {
            return back()->withErrors(['email' => 'Please start registration again.']);
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put(self::OTP_CACHE_PREFIX . $pending['email'], $otp, self::OTP_TTL_SECONDS);

        Mail::to($pending['email'])->send(new RegistrationOtp(
            $pending['email'],
            $otp,
            $pending['name'] ?? ''
        ));

        return back()->with('otp_sent', true)->with('pending_email', $pending['email']);
    }

    /**
     * Handle an incoming registration request (after OTP verification).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|lowercase|email',
            'otp' => 'required|string|size:6',
        ]);

        $pending = $request->session()->get('pending_registration');
        if (!$pending || ($pending['email'] ?? '') !== $request->email) {
            return back()->withErrors(['otp' => 'Please request a new verification code.']);
        }

        $cachedOtp = Cache::get(self::OTP_CACHE_PREFIX . $request->email);
        if ($cachedOtp === null || $cachedOtp !== $request->otp) {
            return back()->withErrors(['otp' => 'The verification code is invalid or has expired.']);
        }

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => Hash::make($pending['password']),
        ]);

        Cache::forget(self::OTP_CACHE_PREFIX . $request->email);
        $request->session()->forget('pending_registration');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
