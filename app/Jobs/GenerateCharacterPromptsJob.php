<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCharacterPromptsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function handle(OpenAIService $ai)
    {
        foreach ($this->project->characters as $character) {

            $prompt = $ai->generateCharacterPrompt(
                $character->name,
                $character->description
            );

            $character->update([
                'image_prompt' => $prompt
            ]);
        }
    }
}
