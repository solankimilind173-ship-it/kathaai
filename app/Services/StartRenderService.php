<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\VideoFormat;
use App\Jobs\RenderProjectJob;
use App\Models\Project;
use App\Models\ProjectEngagement;
use App\Models\RenderLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StartRenderService
{
    public function __construct(
        protected CreditCalculator $creditCalculator,
        protected CreditService $creditService,
        protected VideoMetadataService $metadataService
    ) {}

    /**
     * Start a video render for the project: deduct credits, create render log, dispatch job.
     * Returns the RenderLog when a render was started, or null when skipped (e.g. insufficient credits).
     *
     * @param  array{resolution?: string, format?: string, video_format?: string, fps?: int, subtitle_style?: string, background_music?: bool, auto_render?: bool}  $options
     */
    public function startRender(Project $project, array $options, ?User $user = null): ?RenderLog
    {
        if ($project->is_archived) {
            return null;
        }

        $user = $user ?? $project->user;
        if (! $user) {
            Log::warning('StartRenderService: no user for project', ['project_id' => $project->id]);
            return null;
        }

        $autoRender = (bool) ($options['auto_render'] ?? false);

        if ($autoRender) {
            $maxPerDay = (int) config('kathaai.auto_render_max_per_project_per_day', 1);
            if ($maxPerDay <= 0) {
                Log::info('StartRenderService: auto render disabled by config', [
                    'project_id' => $project->id,
                ]);
                return null;
            }

            $lastAutoRenderAt = $project->last_auto_render_at;
            if ($lastAutoRenderAt && $lastAutoRenderAt->isSameDay(now())) {
                Log::info('StartRenderService: auto render skipped due to daily limit', [
                    'project_id' => $project->id,
                    'last_auto_render_at' => $lastAutoRenderAt,
                ]);
                return null;
            }

            $hasActiveAutoRender = RenderLog::where('project_id', $project->id)
                ->whereIn('status', ['pending', 'rendering'])
                ->exists();

            if ($hasActiveAutoRender) {
                Log::info('StartRenderService: auto render skipped because a render is already pending or running', [
                    'project_id' => $project->id,
                ]);
                return null;
            }
        }

        $resolution = $options['resolution'] ?? $project->quality ?? '1080p';
        $videoFormatValue = $options['video_format'] ?? 'youtube';
        $videoFormat = VideoFormat::tryFrom($videoFormatValue) ?? VideoFormat::YouTube;
        $format = $options['format'] ?? $videoFormat->aspectRatio();
        $fps = (int) ($options['fps'] ?? 24);
        $subtitleStyle = $options['subtitle_style'] ?? 'default';
        $backgroundMusic = (bool) ($options['background_music'] ?? $project->background_music ?? false);

        $plan = $user->plan;
        if ($resolution === '4k' && ! ($plan && $plan->allow_4k)) {
            $resolution = '1080p';
        }

        $durationMinutes = $this->getProjectDurationMinutes($project);
        $amount = $this->creditCalculator->calculateRenderCost([
            'resolution' => $resolution,
            'format' => $format,
            'fps' => $fps,
            'duration_minutes' => $durationMinutes,
            'background_music' => $backgroundMusic,
        ]);

        if (! $this->creditService->hasEnoughCredits($user, $amount)) {
            Log::info('StartRenderService: insufficient credits, skipping render', [
                'project_id' => $project->id,
                'required' => $amount,
                'available' => $user->credits,
            ]);
            return null;
        }

        $metadata = $this->metadataService->generateForProject($project);
        $this->creditService->deductForRender($user, $project, $amount);
        ProjectEngagement::incrementFor($project, 'render_count');

        $renderLog = RenderLog::create([
            'project_id' => $project->id,
            'status' => 'pending',
            'video_format' => $videoFormat->value,
            'video_title' => $metadata['title'],
            'video_description' => $metadata['description'],
            'hashtags' => $metadata['hashtags'],
        ]);

        $update = ['status' => ProjectStatus::Rendering];
        if ($autoRender) {
            $update['last_auto_render_at'] = now();
        }

        $project->update($update);

        RenderProjectJob::dispatch($project, $renderLog, [
            'resolution' => $resolution,
            'format' => $format,
            'video_format' => $videoFormat->value,
            'fps' => $fps,
            'subtitle_style' => $subtitleStyle,
            'background_music' => $backgroundMusic,
        ]);

        return $renderLog;
    }

    public function getProjectDurationMinutes(Project $project): float
    {
        $projectId = $project->id;
        $totalSeconds = $project->scenes()->get()->sum(function ($scene) use ($projectId) {
            $settings = $scene->sceneRenderSettings()->where('project_id', $projectId)->first();
            return $settings?->duration_trimmed ?? $scene->duration ?? 0;
        });
        return round($totalSeconds / 60, 2) ?: (float) ($project->video_minutes ?? 5);
    }
}
