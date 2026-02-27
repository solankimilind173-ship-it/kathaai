<?php

namespace App\Jobs;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Scene;
use App\Jobs\MapSceneCharactersJob;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
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

            // Build locked face references for scene generation (always use for consistency)
            $characters = Character::where('project_id', $episode->project_id)
                ->with('selectedImage')
                ->get();
            $lockedFaceReferences = [];
            foreach ($characters as $character) {
                $ref = $character->getLockedFaceReference();
                if ($ref !== null) {
                    $lockedFaceReferences[] = ['name' => $character->name, 'reference' => $ref];
                }
            }

            // Step 1: AI generate scenes (with locked face reference so descriptions stay consistent)
            $scenes = $ai->generateScenes($episode->summary, $lockedFaceReferences);

            if (!is_array($scenes) || empty($scenes)) {
                Log::warning('AI returned empty scenes', [
                    'episode_id' => $episode->id
                ]);

                $episode->update(['status' => 'scene_failed']);
                return;
            }

            // Step 2: Clean old scenes (re-run safe)
            Scene::where('episode_id', $episode->id)->delete();

            // Step 3: Re-check episode still exists (may have been deleted by another process)
            $episode = Episode::find($this->episodeId);
            if (! $episode) {
                Log::warning('GenerateScenesJob: episode was removed before saving scenes', [
                    'episode_id' => $this->episodeId,
                ]);
                return;
            }
            $episodeId = $episode->id;

            // Step 4: Save scenes in chunks (reduces memory for large episode scene lists)
            try {
                collect($scenes)->filter(fn ($s) => ! empty($s['description'] ?? null))->chunk(20)->each(function ($chunk) use ($episodeId) {
                    foreach ($chunk as $scene) {
                        Scene::create([
                            'episode_id'   => $episodeId,
                            'title'        => $scene['title'] ?? null,
                            'location'     => $scene['location'] ?? null,
                            'time_of_day'  => $scene['time_of_day'] ?? null,
                            'mood'         => $scene['mood'] ?? null,
                            'description'  => $scene['description'],
                            'scene_number' => $scene['scene_number'] ?? 1,
                        ]);
                    }
                });
            } catch (QueryException $e) {
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                    Log::warning('GenerateScenesJob: episode no longer exists, skipping scene insert', [
                        'episode_id' => $this->episodeId,
                    ]);
                    return;
                }
                throw $e;
            }

            // Mark success (re-fetch in case of race)
            $episode = Episode::find($this->episodeId);
            if ($episode) {
                $episode->update(['status' => 'scenes_generated']);
            }

            $episode = Episode::with(['project.user', 'scenes'])->find($this->episodeId);
            if ($episode) {
                $project = $episode->project;
                if ($project && $project->user) {
                    app(\App\Services\NotificationService::class)->sendProjectStepCompleted(
                        $project->user,
                        $project,
                        'Scenes generated',
                        'Scenes for episode "' . ($episode->title ?: 'Episode ' . $episode->episode_number) . '" have been generated.'
                    );
                }
                foreach ($episode->scenes as $scene) {
                    MapSceneCharactersJob::dispatch($scene)->delay(now()->addSeconds(5));
                }
            }

        } catch (Throwable $e) {
            Log::error('GenerateScenesJob crashed', [
                'episode_id' => $this->episodeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (Episode::where('id', $this->episodeId)->exists()) {
                Episode::where('id', $this->episodeId)->update(['status' => 'scene_failed']);
            }
        }
    }
}
