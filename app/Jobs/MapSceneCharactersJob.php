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

    /**
     * Create a new job instance.
     */
    public function __construct(Scene $scene)
    {
        // Laravel yaha pura model serialize nahi karta,
        // sirf model id store hoti hai aur worker pe auto re-fetch hota hai
        $this->scene = $scene;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $ai): void
    {
        // Safety — agar summary missing hai to AI call mat karo
        if (!$this->scene || !$this->scene->description) {
            Log::warning('Scene missing description', [
                'scene_id' => $this->scene?->id
            ]);
            return;
        }

        // Project id nikalna (IMPORTANT — cross project mixing rokne ke liye)
        $projectId = $this->scene->episode->project_id;

        // Project ke characters fetch karo
        $characters = Character::where('project_id', $projectId)
            ->get(['name', 'description'])
            ->toArray();

        if (empty($characters)) {
            Log::warning('No characters found for scene', [
                'scene_id' => $this->scene->id
            ]);
            return;
        }

        // AI se mapping karvao
        $results = $ai->mapSceneCharacters(
            $this->scene->description,
            $characters
        );

        // AI ne galat response diya to crash nahi hona chahiye
        if (!is_array($results)) {
            Log::error('Invalid AI scene-character mapping response', [
                'scene_id' => $this->scene->id,
                'response' => $results
            ]);
            return;
        }

        // Re-run safe — purane mappings delete
        $this->scene->characters()->detach();

        // Save mappings
        foreach ($results as $char) {

            if (!isset($char['name'])) {
                continue;
            }

            $character = Character::where('project_id', $projectId)
                ->where('name', $char['name'])
                ->first();

            if (!$character) {
                Log::warning('Character not matched', [
                    'scene_id' => $this->scene->id,
                    'character_name' => $char['name']
                ]);
                continue;
            }

            $this->scene->characters()->attach($character->id, [
                'action' => $char['action'] ?? null
            ]);
        }

        // Optional progress status
        $this->scene->update([
            'status' => 'characters_mapped'
        ]);
    }
}
