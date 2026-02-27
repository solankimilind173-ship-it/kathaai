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

    public function handle(OpenAIService $ai): void
    {
        $scene = Scene::with('episode')->find($this->sceneId);
        if (! $scene || ! $scene->episode) {
            Log::warning('RegenerateSceneVoiceJob: scene or episode missing', ['scene_id' => $this->sceneId]);
            return;
        }

        $text = trim($scene->description ?? '');
        if ($text === '') {
            Log::warning('RegenerateSceneVoiceJob: scene has no description for TTS', ['scene_id' => $scene->id]);
            if (Scene::where('id', $this->sceneId)->exists()) {
                Scene::where('id', $this->sceneId)->update(['status' => 'voice_failed']);
            }
            return;
        }

        try {
            $scene->update(['status' => 'regenerating_voice']);
            $projectId = $scene->episode->project_id;
            $filename = 'regen-voice-' . $scene->id . '-' . Str::slug(substr($scene->title ?? 's', 0, 20)) . '-' . uniqid();
            $path = $ai->generateSceneVoice($text, $filename, $projectId);
            $scene->update([
                'voice_url' => $path,
                'status' => 'characters_mapped',
            ]);
            Log::info('RegenerateSceneVoiceJob: scene voice updated', ['scene_id' => $scene->id]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning('RegenerateSceneVoiceJob: scene no longer exists', ['scene_id' => $this->sceneId]);
                return;
            }
            throw $e;
        } catch (Throwable $e) {
            Log::error('RegenerateSceneVoiceJob failed', [
                'scene_id' => $this->sceneId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (Scene::where('id', $this->sceneId)->exists()) {
                Scene::where('id', $this->sceneId)->update(['status' => 'voice_failed']);
            }
        }
    }
}
