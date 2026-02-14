<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateCharacterImagesJob implements ShouldQueue
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

            if (!$character->image_prompt) {
                continue;
            }

            $filename = Str::slug($character->name) . '-' . uniqid();

            $path = $ai->generateCharacterImage(
                $character->image_prompt,
                $filename,
                $this->project->id
            );
            $character->update([
                'image_path' => $path,
                'image_generation_completed' => true
            ]);
        }
    }
}
