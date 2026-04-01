<?php

namespace App\Jobs;

use App\Enums\ProjectStatus;
use App\Enums\VideoFormat;
use App\Models\Project;
use App\Services\ElevenLabsService;
use App\Services\OpenAIService;
use App\Services\StartRenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateProjectSceneMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
    }

    public function handle(OpenAIService $ai, ElevenLabsService $elevenLabs): void
    {
        $project = Project::with(['episodes.scenes.characters'])->find($this->projectId);
        if (! $project) {
            Log::warning('GenerateProjectSceneMediaJob: project not found', ['project_id' => $this->projectId]);

            return;
        }

        $scenes = $project->episodes->flatMap->scenes->sortBy([
            fn ($a, $b) => ($a->episode->episode_number ?? 0) <=> ($b->episode->episode_number ?? 0),
            fn ($a, $b) => $a->scene_number <=> $b->scene_number,
        ])->values();

        if ($scenes->isEmpty()) {
            Log::info('GenerateProjectSceneMediaJob: no scenes to process', ['project_id' => $project->id]);
            $this->markReadyAndMaybeRender($project);

            return;
        }

        try {
            foreach ($scenes as $index => $scene) {
                $project = Project::find($this->projectId);
                if (! $project) {
                    return;
                }

                $needsImage = empty(trim($scene->image_url ?? ''));
                $needsVoice = empty(trim($scene->voice_url ?? ''));
                $description = trim($scene->description ?? '');
                if ($description === '' && ($needsImage || $needsVoice)) {
                    Log::warning('GenerateProjectSceneMediaJob: scene has no description, skipping media', [
                        'scene_id' => $scene->id,
                    ]);

                    continue;
                }

                $prefix = 'scene-'.$scene->id.'-'.Str::slug(substr($scene->title ?? 's', 0, 20)).'-'.uniqid();

                if ($needsImage) {
                    try {
                        $imagePrompt = $this->buildSceneImagePrompt($scene, $project);
                        $path = $ai->generateSceneImage($imagePrompt, $prefix.'-img', $project->id);
                        $this->updateScene($scene->id, ['image_url' => $path]);
                    } catch (Throwable $e) {
                        Log::warning('GenerateProjectSceneMediaJob: scene image failed', [
                            'scene_id' => $scene->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if ($needsVoice) {
                    try {
                        $voiceProvider = config('kathaai.voice_provider', 'openai');
                        $useElevenLabs = $voiceProvider === 'elevenlabs' && $elevenLabs->isConfigured();
                        if ($useElevenLabs) {
                            try {
                                $result = $elevenLabs->textToSpeechWithTiming($description, $prefix.'-voice', $project->id);
                                $update = ['voice_url' => $result['audio_path']];
                                if (! empty($result['caption_path'])) {
                                    $update['caption_url'] = $result['caption_path'];
                                }
                                $this->updateScene($scene->id, $update);
                            } catch (Throwable $e) {
                                $path = $elevenLabs->textToSpeech($description, $prefix.'-voice', $project->id);
                                $this->updateScene($scene->id, ['voice_url' => $path]);
                            }
                        } else {
                            $path = $ai->generateSceneVoice($description, $prefix.'-voice', $project->id);
                            $this->updateScene($scene->id, ['voice_url' => $path]);
                        }
                    } catch (Throwable $e) {
                        Log::warning('GenerateProjectSceneMediaJob: scene voice failed', [
                            'scene_id' => $scene->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $project = Project::find($this->projectId);
            if ($project) {
                $project->update(['status' => ProjectStatus::Ready]);
                $project->load('user');
                if ($project->user) {
                    app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                        $project->user,
                        $project,
                        'Scene media generated',
                        'Scene images and voiceovers for your project have been generated. Your video is ready to render.'
                    );
                }
                if (config('kathaai.auto_render_after_pipeline', true)) {
                    $this->startAutoRender($project);
                }
            }
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning('GenerateProjectSceneMediaJob: project or scene no longer exists', [
                    'project_id' => $this->projectId,
                ]);

                return;
            }
            throw $e;
        } catch (Throwable $e) {
            Log::error('GenerateProjectSceneMediaJob failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (Project::where('id', $this->projectId)->exists()) {
                Project::where('id', $this->projectId)->update(['status' => ProjectStatus::Failed]);
            }
        }
    }

    private function buildSceneImagePrompt(\App\Models\Scene $scene, Project $project): string
    {
        $style = $this->getVideoTypeStylePrompt($project->video_type);

        $parts = array_filter([
            $style,
            $scene->description,
            $scene->location ? "Location: {$scene->location}" : null,
            $scene->time_of_day ? "Time: {$scene->time_of_day}" : null,
            $scene->mood ? "Mood: {$scene->mood}" : null,
        ]);

        $prompt = implode('. ', $parts);

        foreach ($scene->characters ?? [] as $character) {
            $action = $character->pivot->action ?? null;
            if (! empty(trim((string) $action))) {
                $prompt .= ' Character '.$character->name.' is '.trim($action).'.';
            }
        }

        return mb_substr(trim($prompt), 0, 4000);
    }

    private function getVideoTypeStylePrompt(?string $videoTypeId): ?string
    {
        $types = config('video_types.types', []);
        foreach ($types as $type) {
            if (($type['id'] ?? null) === $videoTypeId && ! empty($type['description'] ?? null)) {
                return 'Style: '.$type['description'];
            }
        }

        return 'Style: Cinematic, film-like look with depth and mood.';
    }

    private function updateScene(int $sceneId, array $data): void
    {
        try {
            \App\Models\Scene::where('id', $sceneId)->update($data);
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000' && ! str_contains($e->getMessage(), 'foreign key constraint')) {
                throw $e;
            }
        }
    }

    private function markReadyAndMaybeRender(Project $project): void
    {
        $project->update(['status' => ProjectStatus::Ready]);

        if ($this->shouldAutoRender($project)) {
            $this->startAutoRender($project);
        }
    }

    private function shouldAutoRender(Project $project): bool
    {
        if (! config('kathaai.auto_render_after_pipeline', true)) {
            return false;
        }

        if ($project->is_archived || $project->status === ProjectStatus::Rendering) {
            return false;
        }

        $maxPerDay = (int) config('kathaai.auto_render_max_per_project_per_day', 1);
        if ($maxPerDay <= 0) {
            return false;
        }

        $lastAutoRenderAt = $project->last_auto_render_at;
        if ($lastAutoRenderAt && $lastAutoRenderAt->isSameDay(now())) {
            return false;
        }

        return true;
    }

    private function startAutoRender(Project $project): void
    {
        $format = ($project->video_frame === '9:16') ? '9:16' : '16:9';
        $defaultVideoFormat = $project->default_video_format
            ? VideoFormat::tryFrom($project->default_video_format) ?? null
            : null;
        $videoFormat = $defaultVideoFormat
            ?? ($format === '9:16' ? VideoFormat::InstagramReels : VideoFormat::YouTube);
        $options = [
            'resolution' => $project->quality ?? '1080p',
            'format' => $format,
            'video_format' => $videoFormat->value,
            'fps' => $project->default_fps ?? 24,
            'subtitle_style' => $project->default_subtitle_style ?? 'default',
            'background_music' => (bool) ($project->background_music ?? false),
            'auto_render' => true,
        ];
        $renderLog = app(StartRenderService::class)->startRender($project, $options, $project->user);
        if ($renderLog) {
            $project->forceFill(['last_auto_render_at' => now()])->save();
            Log::info('GenerateProjectSceneMediaJob: auto-render started', [
                'project_id' => $project->id,
                'render_log_id' => $renderLog->id,
            ]);
        }
    }
}
