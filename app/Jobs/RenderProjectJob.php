<?php

namespace App\Jobs;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\RenderLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenderProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public RenderLog $renderLog,
        public array $options = []
    ) {}

    public function handle(): void
    {
        $renderLog = $this->renderLog->fresh();
        $project = $this->project->fresh();

        if (! $renderLog || ! $project || $project->id !== $renderLog->project_id) {
            return;
        }

        $renderLog->update([
            'status' => 'rendering',
            'started_at' => now(),
        ]);

        try {
            // TODO: Integrate with actual render pipeline (resolution, format, fps, subtitle_style, background_music from $this->options)
            Log::info('RenderProjectJob: starting render', [
                'project_id' => $project->id,
                'render_log_id' => $renderLog->id,
                'options' => $this->options,
            ]);

            $outputUrl = null; // Replace with real output URL when pipeline is implemented

            $renderLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'output_url' => $outputUrl,
            ]);

            $project->update(['status' => ProjectStatus::Completed]);

            $project->load('user');
            if ($project->user) {
                app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                    $project->user,
                    $project,
                    'Render completed',
                    'Your video render has completed successfully.'
                );
            }
        } catch (\Throwable $e) {
            Log::error('RenderProjectJob failed', [
                'project_id' => $project->id,
                'render_log_id' => $renderLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $renderLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $project->update(['status' => ProjectStatus::Failed]);
            // Do not rethrow: job is handled; user can retry
        }
    }
}
