<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Episode;
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

    public function handle(OpenAIService $ai)
    {
        $project = $this->project->fresh();
        if (! $project) {
            return;
        }

        try {
            $story = $project->chunks()
                ->orderBy('chunk_order')
                ->pluck('chunk_text')
                ->implode("\n");

            $episodes = $ai->generateEpisodes($story);

            $episodeNumber = (int) Episode::where('project_id', $project->id)->max('episode_number') + 1;

            foreach ($episodes as $ep) {
                $episode = Episode::create([
                    'project_id'        => $project->id,
                    'title'             => $ep['title'],
                    'episode_number'    => $episodeNumber++,
                    'summary'           => $ep['summary'],
                    'status'            => 'episode_generated',
                ]);

                GenerateScenesJob::dispatch($episode);
            }

            $project->load('user');
            if ($project->user) {
                app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                    $project->user,
                    $project,
                    'Episodes generated',
                    'Episodes have been created and scene generation has been queued for each.'
                );
            }
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
