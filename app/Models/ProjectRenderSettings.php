<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRenderSettings extends Model
{
    protected $table = 'project_render_settings';

    protected $fillable = [
        'project_id',
        'background_music_url',
        'subtitles_enabled',
        'subtitle_style',
    ];

    protected $casts = [
        'subtitles_enabled' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sceneRenderSettings(): HasMany
    {
        return $this->hasMany(SceneRenderSettings::class, 'project_id', 'project_id');
    }
}
