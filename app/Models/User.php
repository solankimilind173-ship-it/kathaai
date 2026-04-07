<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

/**
 * User model: auth, plan, credits, suspension. Password never exposed in admin.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'google_id',
        'apple_id',
        'plan_id',
        'credits',
        'suspended_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'preferences',
        'onboarding_status',
        'last_onboarding_step',
    ];

    /** Default notification preferences (when not set). */
    public const PREF_EMAIL_WELCOME = 'email_welcome';

    public const PREF_EMAIL_PASSWORD_CHANGED = 'email_password_changed';

    public const PREF_EMAIL_PROJECT_STEP = 'email_project_step';

    public function getPreference(string $key, mixed $default = true): mixed
    {
        $prefs = $this->preferences ?? [];

        return array_key_exists($key, $prefs) ? $prefs[$key] : $default;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_confirmed_at);
    }

    /** Role values that are considered platform admins (excluded from user-only analytics). */
    public const ADMIN_ROLES = ['admin', 'super_admin'];

    /**
     * Scope to only non-admin users (excludes admin and super_admin).
     * Use for dashboard/analytics when showing "users" data only.
     */
    public function scopeNonAdmin(Builder $query): Builder
    {
        return $query->whereNotIn('role', self::ADMIN_ROLES);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function hasCreatedProjects(): bool
    {
        return $this->projects()->exists();
    }

    public function isEligibleForDemoProject(): bool
    {
        return ! $this->hasCreatedProjects();
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Credit ledger: grants (positive amount) and usage (negative amount).
     */
    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Whether the account is suspended (suspended_at set).
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * The attributes that should be hidden for serialization.
     * Admin must never receive password or remember_token (always excluded in toArray/JSON).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'preferences' => 'array',
            'onboarding_status' => 'string',
            'last_onboarding_step' => 'string',
        ];
    }
}
