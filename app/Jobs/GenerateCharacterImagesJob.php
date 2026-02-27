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
use Illuminate\Support\Str;
use Throwable;

class GenerateCharacterImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
    }

    public function handle(OpenAIService $ai): void
    {
        $project = Project::find($this->projectId);
        if (! $project) {
            Log::warning('GenerateCharacterImagesJob: project no longer exists', ['project_id' => $this->projectId]);
            return;
        }

        try {
            $project->characters()->whereNotNull('image_prompt')->chunk(5, function ($characters) use ($ai, $project) {
                foreach ($characters as $character) {
                    $filename = Str::slug($character->name) . '-' . uniqid();

                    $path = $ai->generateCharacterImage(
                        $character->image_prompt,
                        $filename,
                        $project->id
                    );
                    $character->update([
                        'image_path' => $path,
                        'image_generation_completed' => true,
                    ]);
                }
            });

            $project = Project::find($this->projectId);
            if ($project) {
                $project->update(['status' => \App\Enums\ProjectStatus::Ready]);
                $project->load('user');
                if ($project->user) {
                    app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                        $project->user,
                        $project,
                        'Character images generated',
                        'Character images for your project have been generated. Scene media and video render are queued.'
                    );
                }
                // Auto-render is triggered by GenerateProjectSceneMediaJob so the video has scene images and voice
            }
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning('GenerateCharacterImagesJob: project or character no longer exists', [
                    'project_id' => $this->projectId,
                ]);
                return;
            }
            throw $e;
        } catch (Throwable $e) {
            Log::error('GenerateCharacterImagesJob failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (Project::where('id', $this->projectId)->exists()) {
                Project::where('id', $this->projectId)->update(['status' => \App\Enums\ProjectStatus::Failed]);
            }
        }
    }

}
