<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'language',
        'status',
        'image_generation_completed',
        'video_minutes',
        'quality',
        'reels_per_episode',
        'intro_song',
        'background_music',
    ];

    protected $casts = [
        'intro_song' => 'boolean',
        'background_music' => 'boolean',
    ];
    public function chunks()
    {
        return $this->hasMany(StoryChunk::class);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function dubLanguages()
    {
        return $this->belongsToMany(
            Language::class,
            'project_dub_languages'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function renderLogs()
    {
        return $this->hasMany(ProjectRenderLog::class, 'project_id');
    }
}
