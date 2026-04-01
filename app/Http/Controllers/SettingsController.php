<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SecurityEventService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function index(Request $request, SecurityEventService $securityEvent): Response
    {
        $user = $request->user();
        $sessions = $this->getSessionsForUser($user);
        $securityEvents = $securityEvent->getRecentForUser($user, 30);
        $preferences = $this->defaultPreferences($user->preferences ?? []);

        $recoveryCodes = $request->session()->get('two_factor_recovery_codes');
        if ($recoveryCodes) {
            $request->session()->forget('two_factor_recovery_codes');
        }

        return Inertia::render('Settings/Index', [
            'twoFactorEnabled' => (new TwoFactorService)->hasEnabledTwoFactor($user),
            'sessions' => $sessions,
            'securityEvents' => $securityEvents,
            'preferences' => $preferences,
            'recoveryCodes' => $recoveryCodes,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function twoFactorSetup(Request $request, TwoFactorService $twoFactor): Response|RedirectResponse
    {
        $user = $request->user();
        if ($twoFactor->hasEnabledTwoFactor($user)) {
            return redirect()->route('settings.index');
        }

        $secret = $twoFactor->generateSecret();
        $request->session()->put('two_factor.setup_secret', $secret);
        $qrSvg = $twoFactor->getQRCodeSvg($user, $secret);

        return Inertia::render('Settings/TwoFactorSetup', [
            'qrSvg' => $qrSvg,
            'secret' => $secret,
        ]);
    }

    public function confirmTwoFactor(Request $request, TwoFactorService $twoFactor, SecurityEventService $securityEvent): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();
        $secret = $request->session()->get('two_factor.setup_secret');
        if (! $secret) {
            return redirect()->route('settings.index')->withErrors(['code' => 'Setup expired. Please try again.']);
        }

        $code = $request->string('code')->toString();
        if (! $twoFactor->verifyCode($secret, $code)) {
            throw ValidationException::withMessages(['code' => ['The provided code was invalid.']]);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();
        $twoFactor->enableTwoFactor($user, $secret, $code, $recoveryCodes);
        $request->session()->forget('two_factor.setup_secret');

        $securityEvent->log($user, SecurityEventService::TWO_FACTOR_ENABLED, $request);

        return redirect()->route('settings.index')
            ->with('two_factor_recovery_codes', $recoveryCodes)
            ->with('status', 'Two-factor authentication has been enabled.');
    }

    public function showDisableTwoFactor(Request $request): Response
    {
        return Inertia::render('Settings/TwoFactorDisable');
    }

    public function disableTwoFactor(Request $request, TwoFactorService $twoFactor, SecurityEventService $securityEvent): RedirectResponse
    {
        $user = $request->user();
        $twoFactor->disableTwoFactor($user);
        $securityEvent->log($user, SecurityEventService::TWO_FACTOR_DISABLED, $request);

        return redirect()->route('settings.index')->with('status', 'Two-factor authentication has been disabled.');
    }

    public function cancelTwoFactorSetup(Request $request): RedirectResponse
    {
        $request->session()->forget('two_factor.setup_secret');

        return redirect()->route('settings.index');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_welcome' => ['boolean'],
            'email_password_changed' => ['boolean'],
            'email_project_step' => ['boolean'],
        ]);

        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $prefs[User::PREF_EMAIL_WELCOME] = $validated['email_welcome'] ?? true;
        $prefs[User::PREF_EMAIL_PASSWORD_CHANGED] = $validated['email_password_changed'] ?? true;
        $prefs[User::PREF_EMAIL_PROJECT_STEP] = $validated['email_project_step'] ?? true;
        $user->update(['preferences' => $prefs]);

        return redirect()->route('settings.index')->with('status', 'Preferences saved.');
    }

    public function showRevokeSessions(Request $request): Response
    {
        return Inertia::render('Settings/RevokeSessions');
    }

    public function revokeSessions(Request $request, SecurityEventService $securityEvent): RedirectResponse
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        $securityEvent->log($user, SecurityEventService::OTHER_SESSIONS_REVOKED, $request);

        return redirect()->route('settings.index')->with('status', 'All other sessions have been revoked.');
    }

    public function exportData(Request $request, SecurityEventService $securityEvent): StreamedResponse
    {
        $user = $request->user()->load(['plan', 'projects' => fn ($q) => $q->select('id', 'user_id', 'title', 'status', 'created_at')]);

        $securityEvent->log($user, SecurityEventService::DATA_EXPORT_REQUESTED, $request);

        $data = [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
                'plan' => $user->plan ? ['id' => $user->plan->id, 'name' => $user->plan->name] : null,
                'credits' => $user->credits,
            ],
            'projects' => $user->projects->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'status' => $p->status?->value ?? $p->status,
                'created_at' => $p->created_at->toIso8601String(),
            ])->toArray(),
        ];

        $filename = 'kathaai-data-'.$user->id.'-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(
            function () use ($data) {
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
            $filename,
            ['Content-Type' => 'application/json'],
            'attachment'
        );
    }

    private function getSessionsForUser(User $user): array
    {
        $table = config('session.table', 'sessions');
        $sessions = DB::table($table)
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();

        $currentId = session()->getId();

        return $sessions->map(function ($row) use ($currentId) {
            return [
                'id' => $row->id,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'last_activity' => $row->last_activity,
                'is_current' => $row->id === $currentId,
            ];
        })->toArray();
    }

    private function defaultPreferences(array $prefs): array
    {
        return [
            'email_welcome' => $prefs[User::PREF_EMAIL_WELCOME] ?? true,
            'email_password_changed' => $prefs[User::PREF_EMAIL_PASSWORD_CHANGED] ?? true,
            'email_project_step' => $prefs[User::PREF_EMAIL_PROJECT_STEP] ?? true,
        ];
    }
}
