<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SecurityEventService
{
    public const LOGIN = 'login';
    public const LOGOUT = 'logout';
    public const PASSWORD_CHANGED = 'password_changed';
    public const TWO_FACTOR_ENABLED = 'two_factor_enabled';
    public const TWO_FACTOR_DISABLED = 'two_factor_disabled';
    public const EMAIL_CHANGED = 'email_changed';
    public const OTHER_SESSIONS_REVOKED = 'other_sessions_revoked';
    public const DATA_EXPORT_REQUESTED = 'data_export_requested';

    public function log(User $user, string $eventType, ?Request $request = null, array $metadata = []): SecurityEvent
    {
        $request = $request ?? request();

        return SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
            'created_at' => Carbon::now(),
        ]);
    }

    public function getRecentForUser(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return SecurityEvent::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
