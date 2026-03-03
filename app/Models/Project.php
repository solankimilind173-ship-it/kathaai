<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\SourceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'title',
        'description',
        'story_source',
        'source_type',
        'story_from_file',
        'is_public',
        'status',
        'is_archived',
        'total_credits_used',
        'language',
        'image_generation_completed',
        'video_minutes',
        'quality',
        'video_frame',
        'video_type',
        'default_video_format',
        'default_fps',
        'default_subtitle_style',
        'default_subtitles_enabled',
        'reels_per_episode',
        'intro_song',
        'background_music',
        'last_auto_render_at',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'source_type' => SourceType::class,
        'is_public' => 'boolean',
        'story_from_file' => 'boolean',
        'is_archived' => 'boolean',
        'intro_song' => 'boolean',
        'background_music' => 'boolean',
        'total_credits_used' => 'integer',
        'last_auto_render_at' => 'datetime',
        'default_subtitles_enabled' => 'boolean',
    ];

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function chunks()
    {
        return $this->hasMany(StoryChunk::class);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function scenes()
    {
        return $this->hasManyThrough(Scene::class, Episode::class);
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

    /** Per-render-run logs (render_logs table). */
    public function renderRunLogs()
    {
        return $this->hasMany(RenderLog::class, 'project_id');
    }

    public function shareToken()
    {
        return $this->hasOne(ProjectShareToken::class, 'project_id');
    }

    public function engagement()
    {
        return $this->hasOne(ProjectEngagement::class, 'project_id');
    }

    public function renderSettings()
    {
        return $this->hasOne(ProjectRenderSettings::class);
    }

    public function sceneRenderSettings()
    {
        return $this->hasMany(SceneRenderSettings::class, 'project_id');
    }

    /**
     * Credit usage summary: total credits consumed by this project.
     */
    public function getCreditUsageSummaryAttribute(): array
    {
        return [
            'total_credits_used' => (int) $this->total_credits_used,
        ];
    }

    public function scopeStatus(Builder $query, ProjectStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
