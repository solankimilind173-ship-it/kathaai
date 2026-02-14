<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Character;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractCharactersJob implements ShouldQueue
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

        $characters = $ai->extractCharacters($story);

        if (!$characters) return;

        foreach ($characters as $char) {
            Character::create([
                'project_id' => $this->project->id,
                'name' => $char['name'],
                'description' => $char['description'],
            ]);
        }
    }
}
