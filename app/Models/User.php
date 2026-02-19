<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    use HasFactory, Notifiable, Billable;

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
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
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
        ];
    }
}
