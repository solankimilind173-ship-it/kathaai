<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Character;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExtractCharactersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
    }

    public function handle(OpenAIService $ai): void
    {
        $project = Project::find($this->projectId);
        if (! $project) {
            Log::warning('ExtractCharactersJob: project no longer exists', ['project_id' => $this->projectId]);
            return;
        }

        $story = $project->chunks()
            ->orderBy('chunk_order')
            ->pluck('chunk_text')
            ->implode("\n");

        $characters = $ai->extractCharacters($story);

        if (! $characters) {
            return;
        }

        $project = Project::find($this->projectId);
        if (! $project) {
            Log::warning('ExtractCharactersJob: project was removed before creating characters', [
                'project_id' => $this->projectId,
            ]);
            return;
        }

        try {
            foreach ($characters as $char) {
                Character::create([
                    'project_id' => $project->id,
                    'name' => $char['name'],
                    'description' => $char['description'],
                ]);
            }
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning('ExtractCharactersJob: project no longer exists, skipping character create', [
                    'project_id' => $this->projectId,
                ]);
                return;
            }
            throw $e;
        }
    }
}
