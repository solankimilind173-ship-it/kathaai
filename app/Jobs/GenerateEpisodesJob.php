<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Episode;
use App\Services\EpisodeGenerationLimitService;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateEpisodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function handle(OpenAIService $ai, EpisodeGenerationLimitService $limitService): void
    {
        $project = $this->project->fresh();
        if (! $project) {
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

            $episodeNumber = (int) Episode::where('project_id', $project->id)->max('episode_number') + 1;

            foreach ($episodesToCreate as $ep) {
                $episode = Episode::create([
                    'project_id'        => $project->id,
                    'title'             => $ep['title'],
                    'episode_number'    => $episodeNumber++,
                    'summary'           => $ep['summary'],
                    'status'            => 'episode_generated',
                ]);

                GenerateScenesJob::dispatch($episode);
            }

            $createdCount = count($episodesToCreate);
            $message = $createdCount > 0
                ? "{$createdCount} episode(s) have been created and scene generation has been queued."
                : 'Daily episode generation limit reached. Try again tomorrow or upgrade your plan.';

            if ($createdCount > 0 && $createdCount < count($episodes)) {
                $message .= ' Your plan allows ' . $limitService->maxEpisodesPerDay($user) . ' episode(s) per day.';
            }

            app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                $user,
                $project,
                'Episodes generated',
                $message
            );
        } catch (Throwable $e) {
            Log::error('GenerateEpisodesJob failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $project->update(['status' => \App\Enums\ProjectStatus::Failed]);
        }
    }
}
