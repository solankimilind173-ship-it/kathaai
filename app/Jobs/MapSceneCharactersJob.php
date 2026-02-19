<?php

namespace App\Jobs;

use App\Models\Scene;
use App\Models\Character;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MapSceneCharactersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Scene $scene;

    public $tries = 1;
    public $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(Scene $scene)
    {
        $this->scene = $scene;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $ai): void
    {
        if (!$this->scene || !$this->scene->description) {
            Log::warning('Scene missing description', [
                'scene_id' => $this->scene?->id
            ]);
            return;
        }

        $projectId = $this->scene->episode->project_id;

        // Fetch only project characters
        $characters = Character::where('project_id', $projectId)
            ->get(['id', 'name', 'description']);

        if ($characters->isEmpty()) {
            Log::warning('No characters found for scene', [
                'scene_id' => $this->scene->id
            ]);
            return;
        }

        // Prepare allowed names list (case-insensitive)
        $allowedNames = $characters
            ->pluck('name')
            ->map(fn($name) => strtolower(trim($name)))
            ->toArray();

        // AI mapping call
        $results = $ai->mapSceneCharacters(
            $this->scene->description,
            $characters->toArray()
        );

        if (!is_array($results)) {
            Log::error('Invalid AI scene-character mapping response', [
                'scene_id' => $this->scene->id,
                'response' => $results
            ]);
            return;
        }

        // Re-run safe
        $this->scene->characters()->detach();

        foreach ($results as $char) {

            if (!isset($char['name'])) {
                continue;
            }

            $aiName = strtolower(trim($char['name']));

            // 🔥 IMPORTANT: Ignore generic groups
            if (!in_array($aiName, $allowedNames)) {
                continue;
            }

            $character = $characters
                ->firstWhere(fn($c) => strtolower($c->name) === $aiName);

            if (!$character) {
                continue;
            }

            // Avoid duplicate attach
            if (!$this->scene->characters()->where('character_id', $character->id)->exists()) {
                $this->scene->characters()->attach($character->id, [
                    'action' => $char['action'] ?? null
                ]);
            }
        }

        $this->scene->update([
            'status' => 'characters_mapped'
        ]);
    }
}
