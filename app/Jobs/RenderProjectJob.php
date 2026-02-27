<?php

namespace App\Jobs;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\RenderLog;
use App\Services\VideoRenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenderProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        public Project $project,
        public RenderLog $renderLog,
        public array $options = []
    ) {}

    public function handle(VideoRenderService $renderService): void
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
            Log::info('RenderProjectJob: starting render', [
                'project_id' => $project->id,
                'render_log_id' => $renderLog->id,
                'options' => $this->options,
            ]);

            $result = $renderService->render($project, $renderLog, array_merge($this->options, [
                'apply_watermark' => true,
            ]));

            $renderLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'output_url' => $result['output_url'],
                'thumbnail_url' => $result['thumbnail_url'],
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
