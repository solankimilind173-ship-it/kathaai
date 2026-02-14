<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\Scene;
use App\Services\OpenAIService;
use App\Jobs\MapSceneCharactersJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateScenesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $episodeId;
    public $tries = 1;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(Episode $episode)
    {
        // NEVER store full model in queue
        $this->episodeId = $episode->id;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $ai): void
    {
        try {

            // 🔴 ALWAYS RELOAD FRESH MODEL
            $episode = Episode::find($this->episodeId);

            if (!$episode || !$episode->summary) {
                Log::warning('Episode missing or empty summary', [
                    'episode_id' => $this->episodeId
                ]);
                return;
            }

            // Mark processing (useful for UI)
            $episode->update(['status' => 'generating_scenes']);

            // Step 1: AI generate scenes
            $scenes = $ai->generateScenes($episode->summary);

            if (!is_array($scenes) || empty($scenes)) {
                Log::warning('AI returned empty scenes', [
                    'episode_id' => $episode->id
                ]);

                $episode->update(['status' => 'scene_failed']);
                return;
            }

            // Step 2: Clean old scenes (re-run safe)
            Scene::where('episode_id', $episode->id)->delete();

            // Step 3: Save scenes
            foreach ($scenes as $scene) {

                if (!isset($scene['description'])) {
                    continue;
                }

                Scene::create([
                    'episode_id'   => $episode->id,
                    'title'        => $scene['title'] ?? null,
                    'location'     => $scene['location'] ?? null,
                    'time_of_day'  => $scene['time_of_day'] ?? null,
                    'mood'         => $scene['mood'] ?? null,
                    'description'  => $scene['description'],
                    'scene_number' => $scene['scene_number'] ?? 1,
                ]);
            }

            // Mark success
            $episode->update(['status' => 'scenes_generated']);

            // 🔴 IMPORTANT: reload scenes from DB
            $episode->load('scenes');

            // Step 4: Dispatch character mapping
            foreach ($episode->scenes as $scene) {
                MapSceneCharactersJob::dispatch($scene)->delay(now()->addSeconds(5));
            }

        } catch (Throwable $e) {

            Log::error('GenerateScenesJob crashed', [
                'episode_id' => $this->episodeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fail gracefully
            Episode::where('id', $this->episodeId)
                ->update(['status' => 'scene_failed']);
        }
    }
}
