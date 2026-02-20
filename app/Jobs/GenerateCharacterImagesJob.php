<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateCharacterImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;

    public int $tries = 2;

    public int $timeout = 600;

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

            $project->load('user');
            if ($project->user) {
                app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                    $project->user,
                    $project,
                    'Character images generated',
                    'Character images for your project have been generated. Your project is one step closer to being ready.'
                );
            }
        } catch (Throwable $e) {
            Log::error('GenerateCharacterImagesJob failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $project->update(['status' => \App\Enums\ProjectStatus::Failed]);
        }
    }
}
