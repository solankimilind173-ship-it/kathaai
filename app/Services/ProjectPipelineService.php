<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Jobs\GenerateProjectStructureJob;
use App\Models\Project;

class ProjectPipelineService
{
    public function startGeneration(Project $project): void
    {
        $project->update(['status' => ProjectStatus::Generating]);

        GenerateProjectStructureJob::dispatch($project);
    }

    /**
     * @return array{
     *   counts: array<string, int>,
     *   progress_percentage: int,
     *   next_action: string,
     *   stages: array<int, array{key: string, label: string, status: string, detail: string}>
     * }
     */
    public function summarize(Project $project): array
    {
        $project->loadMissing([
            'chunks:id,project_id',
            'episodes:id,project_id,status',
            'scenes',
            'characters:id,project_id,image_path,image_url',
            'renderRunLogs:id,project_id,status',
        ]);

        $counts = [
            'story_chunks' => $project->chunks->count(),
            'episodes' => $project->episodes->count(),
            'episodes_with_scenes' => $project->episodes
                ->filter(fn ($episode) => $project->scenes->contains('episode_id', $episode->id))
                ->count(),
            'scenes' => $project->scenes->count(),
            'scenes_with_images' => $project->scenes->filter(fn ($scene) => filled($scene->image_url))->count(),
            'scenes_with_voices' => $project->scenes->filter(fn ($scene) => filled($scene->voice_url))->count(),
            'scene_failures' => $project->scenes
                ->filter(fn ($scene) => in_array($scene->status, ['image_failed', 'voice_failed'], true))
                ->count(),
            'characters' => $project->characters->count(),
            'characters_with_images' => $project->characters
                ->filter(fn ($character) => filled($character->image_path) || filled($character->image_url))
                ->count(),
            'completed_renders' => $project->renderRunLogs->where('status', 'completed')->count(),
        ];

        $stages = [
            [
                'key' => 'story',
                'label' => 'Story ingestion',
                'status' => $counts['story_chunks'] > 0 ? 'completed' : $this->statusForInitialStage($project),
                'detail' => $counts['story_chunks'] > 0
                    ? "{$counts['story_chunks']} story chunk(s) prepared for generation."
                    : 'Waiting to prepare the story input for AI planning.',
            ],
            [
                'key' => 'episodes',
                'label' => 'Episode planning',
                'status' => $counts['episodes'] > 0 ? 'completed' : $this->statusForGeneratingStage($project, $counts['story_chunks'] > 0),
                'detail' => $counts['episodes'] > 0
                    ? "{$counts['episodes']} episode(s) generated from the story."
                    : 'Episodes have not been generated yet.',
            ],
            [
                'key' => 'scenes',
                'label' => 'Scene breakdown',
                'status' => $this->sceneStageStatus($project, $counts),
                'detail' => $this->sceneStageDetail($counts),
            ],
            [
                'key' => 'characters',
                'label' => 'Character design',
                'status' => $this->characterStageStatus($project, $counts),
                'detail' => $this->characterStageDetail($counts),
            ],
            [
                'key' => 'media',
                'label' => 'Scene media generation',
                'status' => $this->mediaStageStatus($project, $counts),
                'detail' => $this->mediaStageDetail($counts),
            ],
            [
                'key' => 'render',
                'label' => 'Video render',
                'status' => $this->renderStageStatus($project, $counts),
                'detail' => $this->renderStageDetail($project, $counts),
            ],
        ];

        $progressPercentage = (int) round(
            collect($stages)->reduce(
                fn (int $carry, array $stage) => $carry + match ($stage['status']) {
                    'completed' => 100,
                    'current' => 50,
                    'failed' => 0,
                    default => 0,
                },
                0
            ) / max(count($stages), 1)
        );

        return [
            'counts' => $counts,
            'progress_percentage' => $progressPercentage,
            'next_action' => $this->nextAction($project, $stages),
            'stages' => $stages,
        ];
    }

    public function syncProjectStatus(Project $project): ProjectStatus
    {
        if ($project->is_archived) {
            return ProjectStatus::Archived;
        }

        if ($project->status === ProjectStatus::Completed || $project->status === ProjectStatus::Rendering || $project->status === ProjectStatus::Failed) {
            return $project->status;
        }

        $summary = $this->summarize($project);
        $counts = $summary['counts'];

        $status = match (true) {
            $counts['scenes'] > 0
                && $counts['scenes'] === $counts['scenes_with_images']
                && $counts['scenes'] === $counts['scenes_with_voices'] => ProjectStatus::Ready,
            $counts['story_chunks'] > 0 || $counts['episodes'] > 0 || $counts['characters'] > 0 => ProjectStatus::Generating,
            default => ProjectStatus::Draft,
        };

        if ($project->status !== $status) {
            $project->update(['status' => $status]);
        }

        return $status;
    }

    /**
     * @return array{ready: bool, reason: string}
     */
    public function sceneMediaReadiness(Project $project): array
    {
        $project->loadMissing([
            'episodes:id,project_id,status',
            'scenes',
        ]);

        if ($project->episodes->isEmpty()) {
            return [
                'ready' => false,
                'reason' => 'Episode planning has not finished yet.',
            ];
        }

        $pendingSceneEpisodes = $project->episodes
            ->filter(fn ($episode) => in_array($episode->status, ['episode_generated', 'generating_scenes'], true));

        if ($pendingSceneEpisodes->isNotEmpty()) {
            return [
                'ready' => false,
                'reason' => 'Scene breakdown is still running for one or more episodes.',
            ];
        }

        if ($project->scenes->isEmpty()) {
            return [
                'ready' => false,
                'reason' => 'No scenes exist yet for media generation.',
            ];
        }

        return [
            'ready' => true,
            'reason' => 'Scenes are ready for media generation.',
        ];
    }

    private function statusForInitialStage(Project $project): string
    {
        return $project->status === ProjectStatus::Failed ? 'failed' : 'pending';
    }

    private function statusForGeneratingStage(Project $project, bool $prerequisiteMet): string
    {
        if ($project->status === ProjectStatus::Failed) {
            return 'failed';
        }

        return $prerequisiteMet ? 'current' : 'pending';
    }

    private function sceneStageStatus(Project $project, array $counts): string
    {
        if ($project->status === ProjectStatus::Failed) {
            return 'failed';
        }

        if ($counts['scenes'] > 0 && $counts['episodes_with_scenes'] >= $counts['episodes'] && $counts['episodes'] > 0) {
            return 'completed';
        }

        return $counts['episodes'] > 0 ? 'current' : 'pending';
    }

    private function sceneStageDetail(array $counts): string
    {
        if ($counts['scenes'] === 0) {
            return 'Scenes have not been generated yet.';
        }

        return "{$counts['scenes']} scene(s) generated across {$counts['episodes_with_scenes']}/{$counts['episodes']} episode(s).";
    }

    private function characterStageStatus(Project $project, array $counts): string
    {
        if ($project->status === ProjectStatus::Failed) {
            return 'failed';
        }

        if ($counts['characters'] > 0 && $counts['characters'] === $counts['characters_with_images']) {
            return 'completed';
        }

        return $counts['characters'] > 0 ? 'current' : 'pending';
    }

    private function characterStageDetail(array $counts): string
    {
        if ($counts['characters'] === 0) {
            return 'Characters have not been extracted yet.';
        }

        return "{$counts['characters_with_images']}/{$counts['characters']} character(s) have visual references.";
    }

    private function mediaStageStatus(Project $project, array $counts): string
    {
        if ($project->status === ProjectStatus::Failed || $counts['scene_failures'] > 0) {
            return 'failed';
        }

        if ($counts['scenes'] > 0
            && $counts['scenes'] === $counts['scenes_with_images']
            && $counts['scenes'] === $counts['scenes_with_voices']) {
            return 'completed';
        }

        return $counts['scenes'] > 0 ? 'current' : 'pending';
    }

    private function mediaStageDetail(array $counts): string
    {
        if ($counts['scenes'] === 0) {
            return 'Scene media will start after scene generation completes.';
        }

        if ($counts['scene_failures'] > 0) {
            return "{$counts['scene_failures']} scene media step(s) need attention before rendering.";
        }

        return "{$counts['scenes_with_images']}/{$counts['scenes']} images and {$counts['scenes_with_voices']}/{$counts['scenes']} voice tracks generated.";
    }

    private function renderStageStatus(Project $project, array $counts): string
    {
        return match ($project->status) {
            ProjectStatus::Completed => 'completed',
            ProjectStatus::Rendering => 'current',
            ProjectStatus::Failed => 'failed',
            default => $counts['completed_renders'] > 0 ? 'completed' : 'pending',
        };
    }

    private function renderStageDetail(Project $project, array $counts): string
    {
        return match ($project->status) {
            ProjectStatus::Completed => 'Your cinematic video has been rendered successfully.',
            ProjectStatus::Rendering => 'Rendering is currently in progress.',
            ProjectStatus::Failed => 'The last pipeline or render attempt failed and can be retried.',
            default => $counts['completed_renders'] > 0
                ? "{$counts['completed_renders']} completed render(s) available."
                : 'Render will become available once scene media is ready.',
        };
    }

    /**
     * @param  array<int, array{key: string, label: string, status: string, detail: string}>  $stages
     */
    private function nextAction(Project $project, array $stages): string
    {
        if ($project->status === ProjectStatus::Failed) {
            return 'Review the failed step, then retry generation or render from the project page.';
        }

        foreach ($stages as $stage) {
            if ($stage['status'] === 'current') {
                return $stage['detail'];
            }
        }

        if ($project->status === ProjectStatus::Ready) {
            return 'Scene media is ready. Start the final render when you are happy with the scenes.';
        }

        if ($project->status === ProjectStatus::Completed) {
            return 'Your latest rendered video is ready to review, share, and download.';
        }

        return 'Add or refine story content, then continue the cinematic generation pipeline.';
    }
}
