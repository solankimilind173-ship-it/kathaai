<?php

namespace App\Jobs;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunDailyRenderPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
    }

    public function handle(): void
    {
        $project = Project::with('episodes')->find($this->projectId);

        if (! $project) {
            Log::warning('RunDailyRenderPipelineJob: project not found', ['project_id' => $this->projectId]);
            return;
        }

        if ($project->is_archived || $project->status === ProjectStatus::Rendering) {
            return;
        }

        if (! $project->episodes || $project->episodes->isEmpty()) {
            Log::info('RunDailyRenderPipelineJob: project has no episodes, skipping auto render', [
                'project_id' => $project->id,
            ]);
            return;
        }

        GenerateProjectSceneMediaJob::dispatch($project);
    }
}

