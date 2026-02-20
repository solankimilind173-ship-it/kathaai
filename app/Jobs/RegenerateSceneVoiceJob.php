<?php

namespace App\Jobs;

use App\Models\Scene;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegenerateSceneVoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sceneId;

    public int $tries = 2;

    public int $timeout = 120;

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

        try {
            // TODO: Call TTS/voice API, store result in scene.voice_url
            Log::info('RegenerateSceneVoiceJob: scene', ['scene_id' => $scene->id]);
            $scene->update(['status' => 'scenes_generated']);
        } catch (Throwable $e) {
            Log::error('RegenerateSceneVoiceJob failed', [
                'scene_id' => $scene->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $scene->update(['status' => 'voice_failed']);
        }
    }
}
