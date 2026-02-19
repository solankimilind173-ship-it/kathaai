<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scene extends Model
{
    protected $fillable = [
        'episode_id',
        'title',
        'description',
        'image_url',
        'voice_url',
        'duration',
        'credits_used',
        'status',
        'camera_style',
        'lighting',
        'location',
        'time_of_day',
        'mood',
        'scene_number',
    ];

    protected $casts = [
        'duration' => 'integer',
        'credits_used' => 'integer',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class)
            ->withPivot('action')
            ->withTimestamps();
    }

    public function sceneRenderSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SceneRenderSettings::class);
    }
}
