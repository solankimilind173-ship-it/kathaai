<?php

namespace App\Jobs;

use App\Models\Scene;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegenerateSceneImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sceneId;

    public function __construct(Scene $scene)
    {
        $this->sceneId = $scene->id;
    }

    public function handle(): void
    {
        $scene = Scene::find($this->sceneId);
        if (! $scene) {
            return;
        }

        // TODO: Call image generation API (e.g. OpenAIService or DALL-E), store result in scene.image_url
        // For now just mark as completed so UI can reflect; replace with actual generation.
        Log::info('RegenerateSceneImageJob: scene', ['scene_id' => $scene->id]);

        $scene->update(['status' => 'scenes_generated']);
    }
}
