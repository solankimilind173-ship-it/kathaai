<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenderLog extends Model
{
    protected $table = 'render_logs';

    protected $fillable = [
        'project_id',
        'status',
        'video_format',
        'started_at',
        'completed_at',
        'output_url',
        'thumbnail_url',
        'video_title',
        'video_description',
        'hashtags',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['video_format_label'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getVideoFormatLabelAttribute(): ?string
    {
        $value = $this->getAttributeFromArray('video_format');
        if ($value === null || $value === '') {
            return null;
        }

        return match ($value) {
            'instagram_reels' => 'Instagram Reels',
            'youtube' => 'YouTube',
            default => $value,
        };
    }
}
