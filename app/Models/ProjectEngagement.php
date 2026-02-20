<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEngagement extends Model
{
    protected $table = 'project_engagements';

    protected $fillable = [
        'project_id',
        'view_count',
        'share_count',
        'render_count',
        'clone_count',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'share_count' => 'integer',
        'render_count' => 'integer',
        'clone_count' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get or create engagement for a project, then increment a counter.
     */
    public static function incrementFor(Project $project, string $counter): void
    {
        $engagement = $project->engagement()->firstOrCreate(
            ['project_id' => $project->id],
            ['view_count' => 0, 'share_count' => 0, 'render_count' => 0, 'clone_count' => 0]
        );

        if (in_array($counter, ['view_count', 'share_count', 'render_count', 'clone_count'], true)) {
            $engagement->increment($counter);
        }
    }
}
