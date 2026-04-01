<?php

namespace App\Jobs;

use App\Models\Scene;
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

class RegenerateSceneImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sceneId;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(Scene $scene)
    {
        $this->sceneId = $scene->id;
    }

    public function handle(OpenAIService $ai): void
    {
        $scene = Scene::with('episode')->find($this->sceneId);
        if (! $scene || ! $scene->episode) {
            Log::warning('RegenerateSceneImageJob: scene or episode missing', ['scene_id' => $this->sceneId]);

            return;
        }

        $description = trim($scene->description ?? '');
        if ($description === '') {
            Log::warning('RegenerateSceneImageJob: scene has no description', ['scene_id' => $scene->id]);
            if (Scene::where('id', $this->sceneId)->exists()) {
                Scene::where('id', $this->sceneId)->update(['status' => 'image_failed']);
            }

            return;
        }

        try {
            $scene->update(['status' => 'regenerating_image']);
            $projectId = $scene->episode->project_id;
            $prompt = $this->buildSceneImagePrompt($scene);
            $filename = 'regen-scene-'.$scene->id.'-'.Str::slug(substr($scene->title ?? 's', 0, 20)).'-'.uniqid();
            $path = $ai->generateSceneImage($prompt, $filename, $projectId);
            $scene->update([
                'image_url' => $path,
                'status' => 'characters_mapped',
            ]);
            Log::info('RegenerateSceneImageJob: scene image updated', ['scene_id' => $scene->id]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning('RegenerateSceneImageJob: scene no longer exists', ['scene_id' => $this->sceneId]);

                return;
            }
            throw $e;
        } catch (Throwable $e) {
            Log::error('RegenerateSceneImageJob failed', [
                'scene_id' => $this->sceneId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (Scene::where('id', $this->sceneId)->exists()) {
                Scene::where('id', $this->sceneId)->update(['status' => 'image_failed']);
            }
        }
    }

    private function buildSceneImagePrompt(Scene $scene): string
    {
        $parts = array_filter([
            $scene->description,
            $scene->location ? "Location: {$scene->location}" : null,
            $scene->time_of_day ? "Time: {$scene->time_of_day}" : null,
            $scene->mood ? "Mood: {$scene->mood}" : null,
        ]);
        $prompt = implode('. ', $parts);

        return mb_substr(trim($prompt), 0, 4000);
    }
}
