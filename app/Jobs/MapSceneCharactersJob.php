<?php

namespace App\Jobs;

use App\Models\Scene;
use App\Models\Character;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MapSceneCharactersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sceneId;

    public $tries = 1;

    public $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(Scene $scene)
    {
        $this->sceneId = $scene->id;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $ai): void
    {
        $scene = Scene::with('episode')->find($this->sceneId);

        if (! $scene || ! $scene->episode) {
            Log::warning('MapSceneCharactersJob: scene or episode missing', [
                'scene_id' => $this->sceneId,
            ]);
            return;
        }

        if (empty(trim($scene->description ?? ''))) {
            Log::warning('Scene missing description', ['scene_id' => $this->sceneId]);
            return;
        }

        $projectId = $scene->episode->project_id;

        $characters = Character::where('project_id', $projectId)
            ->get(['id', 'name', 'description']);

        if ($characters->isEmpty()) {
            Log::warning('No characters found for scene', ['scene_id' => $this->sceneId]);
            return;
        }

        $allowedNames = $characters
            ->pluck('name')
            ->map(fn ($name) => strtolower(trim($name)))
            ->toArray();

        $results = $ai->mapSceneCharacters(
            $scene->description,
            $characters->toArray()
        );

        if (! is_array($results)) {
            Log::error('Invalid AI scene-character mapping response', [
                'scene_id' => $this->sceneId,
                'response' => $results,
            ]);
            return;
        }

        try {
            $scene->characters()->detach();

            foreach ($results as $char) {
                if (! isset($char['name'])) {
                    continue;
                }

                $aiName = strtolower(trim($char['name']));

                if (! in_array($aiName, $allowedNames)) {
                    continue;
                }

                $character = $characters->firstWhere(fn ($c) => strtolower($c->name) === $aiName);

                if (! $character) {
                    continue;
                }

                if (! $scene->characters()->where('character_id', $character->id)->exists()) {
                    $scene->characters()->attach($character->id, [
                        'action' => $char['action'] ?? null,
                    ]);
                }
            }

            $scene->update(['status' => 'characters_mapped']);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning('MapSceneCharactersJob: scene or related record no longer exists', [
                    'scene_id' => $this->sceneId,
                ]);
                return;
            }
            throw $e;
        }
    }
}
