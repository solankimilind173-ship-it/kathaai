<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\StoryChunk;
use App\Services\StoryChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateProjectStructureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
    }

    public function handle(StoryChunker $chunker): void
    {
        $project = Project::find($this->projectId);

        if (! $project) {
            Log::warning('GenerateProjectStructureJob: project not found', ['project_id' => $this->projectId]);
            return;
        }

        try {
            $story = $this->getStoryContent($project);

            if (empty(trim($story ?? ''))) {
                Log::warning('GenerateProjectStructureJob: no story content', ['project_id' => $project->id]);
                $project->update(['status' => \App\Enums\ProjectStatus::Failed]);
                return;
            }

            $chunks = $chunker->chunk($story);

            foreach ($chunks as $index => $chunk) {
                StoryChunk::create([
                    'project_id' => $project->id,
                    'chunk_text' => $chunk,
                    'chunk_order' => $index,
                    'token_count' => (int) (strlen($chunk) / 4),
                ]);
            }

            GenerateEpisodesJob::dispatch($project);
            ExtractCharactersJob::dispatch($project)->delay(now()->addSeconds(25));
            GenerateCharacterPromptsJob::dispatch($project)->delay(now()->addSeconds(60));
            GenerateCharacterImagesJob::dispatch($project)->delay(now()->addSeconds(110));

        } catch (Throwable $e) {
            Log::error('GenerateProjectStructureJob failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $project->update(['status' => \App\Enums\ProjectStatus::Failed]);
            throw $e;
        }
    }

    private function getStoryContent(Project $project): string
    {
        if ($project->story_source) {
            return $project->story_source;
        }

        if ($project->book_id && $project->book) {
            return $project->book->content ?? '';
        }

        return '';
    }
}
