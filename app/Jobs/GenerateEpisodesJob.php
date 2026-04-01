<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\Project;
use App\Services\EpisodeGenerationLimitService;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateEpisodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public int $tries = 5;

    public int $timeout = 300;

    /**
     * Seconds to wait before retrying after a failure (e.g. rate limit).
     */
    public function backoff(): array
    {
        return [60, 120, 300, 600];
    }

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
    }

    public function handle(OpenAIService $ai, EpisodeGenerationLimitService $limitService): void
    {
        $project = Project::find($this->projectId);
        if (! $project) {
            Log::warning('GenerateEpisodesJob: project no longer exists', ['project_id' => $this->projectId]);

            return;
        }

        $user = $project->user;
        if (! $user) {
            return;
        }

        $remainingSlots = $limitService->remainingSlotsToday($user);
        if ($remainingSlots <= 0) {
            Log::info('GenerateEpisodesJob: daily episode limit reached', [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);
            $project->update(['status' => \App\Enums\ProjectStatus::Failed]);
            app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                $user,
                $project,
                'Daily limit reached',
                $limitService->limitReachedMessage($user)
            );

            return;
        }

        try {
            $story = $project->chunks()
                ->orderBy('chunk_order')
                ->pluck('chunk_text')
                ->implode("\n");

            $episodes = $ai->generateEpisodes($story);

            // Cap to plan's daily episode generation limit (e.g. 1 for Basic, more for higher plans)
            $episodesToCreate = array_slice($episodes, 0, $remainingSlots);

            $project = Project::find($this->projectId);
            if (! $project) {
                Log::warning('GenerateEpisodesJob: project was removed before creating episodes', ['project_id' => $this->projectId]);

                return;
            }

            $episodeNumber = (int) Episode::where('project_id', $project->id)->max('episode_number') + 1;

            foreach ($episodesToCreate as $ep) {
                try {
                    $episode = Episode::create([
                        'project_id' => $project->id,
                        'title' => $ep['title'],
                        'episode_number' => $episodeNumber++,
                        'summary' => $ep['summary'],
                        'status' => 'episode_generated',
                    ]);
                    GenerateScenesJob::dispatch($episode);
                } catch (QueryException $e) {
                    if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                        Log::warning('GenerateEpisodesJob: project no longer exists, skipping episode create', [
                            'project_id' => $this->projectId,
                        ]);

                        return;
                    }
                    throw $e;
                }
            }

            $createdCount = count($episodesToCreate);
            $message = $createdCount > 0
                ? "{$createdCount} episode(s) have been created and scene generation has been queued."
                : 'Daily episode generation limit reached. Try again tomorrow or upgrade your plan.';

            if ($createdCount > 0 && $createdCount < count($episodes)) {
                $message .= ' Your plan allows '.$limitService->maxEpisodesPerDay($user).' episode(s) per day.';
            }

            app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                $user,
                $project,
                'Episodes generated',
                $message
            );
        } catch (Throwable $e) {
            $isRateLimit = stripos($e->getMessage(), 'rate limit') !== false
                || stripos($e->getMessage(), '429') !== false;

            if ($isRateLimit && $this->attempts() < $this->tries) {
                Log::warning('GenerateEpisodesJob: rate limit hit, releasing back to queue', [
                    'project_id' => $this->projectId,
                    'attempt' => $this->attempts(),
                    'retry_after' => 90,
                ]);
                $this->release(90);

                return;
            }

            Log::error('GenerateEpisodesJob failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (Project::where('id', $this->projectId)->exists()) {
                Project::where('id', $this->projectId)->update(['status' => \App\Enums\ProjectStatus::Failed]);
            }
            throw $e;
        }
    }
}
