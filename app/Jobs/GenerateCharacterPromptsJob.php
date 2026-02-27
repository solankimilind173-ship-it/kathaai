<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCharacterPromptsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
    }

    public function handle(OpenAIService $ai): void
    {
        $project = Project::with('characters')->find($this->projectId);
        if (! $project) {
            Log::warning('GenerateCharacterPromptsJob: project no longer exists', ['project_id' => $this->projectId]);
            return;
        }

        foreach ($project->characters as $character) {
            try {
                $prompt = $ai->generateCharacterPrompt(
                    $character->name,
                    $character->description
                );
                $character->update(['image_prompt' => $prompt]);
            } catch (QueryException $e) {
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                    Log::warning('GenerateCharacterPromptsJob: character or project no longer exists', [
                        'project_id' => $this->projectId,
                    ]);
                    return;
                }
                throw $e;
            }
        }
    }
}
