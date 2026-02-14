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

class GenerateEpisodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function handle(OpenAIService $ai)
    {
        $story = $this->project->chunks()
            ->orderBy('chunk_order')
            ->pluck('chunk_text')
            ->implode("\n");

        $episodes = $ai->generateEpisodes($story);

        foreach ($episodes as $ep) {

            $episode = Episode::create([
                'project_id' => $this->project->id,
                'title'      => $ep['title'],
                'summary'    => $ep['summary'],
                'status'     => 'episode_generated',
            ]);

            // Generate scenes for this episode
            GenerateScenesJob::dispatch($episode);
        }
    }
}
