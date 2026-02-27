<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\StoryChunk;
use App\Services\StoryChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
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

            $project = Project::find($this->projectId);
            if (! $project) {
                Log::warning('GenerateProjectStructureJob: project was removed before creating chunks', [
                    'project_id' => $this->projectId,
                ]);
                return;
            }

            try {
                foreach ($chunks as $index => $chunk) {
                    StoryChunk::create([
                        'project_id' => $project->id,
                        'chunk_text' => $chunk,
                        'chunk_order' => $index,
                        'token_count' => (int) (strlen($chunk) / 4),
                    ]);
                }
            } catch (QueryException $e) {
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                    Log::warning('GenerateProjectStructureJob: project no longer exists, skipping chunk create', [
                        'project_id' => $this->projectId,
                    ]);
                    return;
                }
                throw $e;
            }

            GenerateEpisodesJob::dispatch($project);
            ExtractCharactersJob::dispatch($project)->delay(now()->addSeconds(25));
            GenerateCharacterPromptsJob::dispatch($project)->delay(now()->addSeconds(60));
            GenerateCharacterImagesJob::dispatch($project)->delay(now()->addSeconds(110));
            GenerateProjectSceneMediaJob::dispatch($project)->delay(now()->addSeconds(90));

            $project->load('user');
            if ($project->user) {
                app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                    $project->user,
                    $project,
                    'Structure generated',
                    'Your story has been chunked and episode generation has been queued.'
                );
            }
        } catch (Throwable $e) {
            Log::error('GenerateProjectStructureJob failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (Project::where('id', $this->projectId)->exists()) {
                Project::where('id', $this->projectId)->update(['status' => \App\Enums\ProjectStatus::Failed]);
            }
            // Do not rethrow: job is considered handled so user can retry manually
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
