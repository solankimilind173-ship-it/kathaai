<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SceneRenderSettings extends Model
{
    protected $table = 'scene_render_settings';

    protected $fillable = [
        'project_id',
        'scene_id',
        'sort_order',
        'duration_trimmed',
        'transition_style',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'duration_trimmed' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }
}
