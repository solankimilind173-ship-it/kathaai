<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger entry for user credits. Positive amount = granted, negative = used.
 * Type: admin_adjustment, monthly_grant, usage.
 */
class CreditTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'scene_id',
        'action_type',
        'credits',
        'amount',
        'type',
        'feature',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    /**
     * Scope: usage transactions only (credit deductions).
     */
    public function scopeUsage($query)
    {
        return $query->where('type', 'usage');
    }
}

