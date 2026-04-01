<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Scene;
use Illuminate\Support\Facades\Cache;

class RegenerationLimitService
{
    private const TTL_SECONDS = 86400; // 24 hours

    public function canRegenerateSceneImage(Scene $scene): bool
    {
        $limit = config('regeneration.max_scene_image_per_scene_per_day', 10);
        $key = $this->sceneImageKey($scene);

        return (int) Cache::get($key, 0) < $limit;
    }

    public function recordSceneImageRegeneration(Scene $scene): void
    {
        $key = $this->sceneImageKey($scene);
        $this->incrementKey($key);
    }

    public function canRegenerateSceneVoice(Scene $scene): bool
    {
        $limit = config('regeneration.max_scene_voice_per_scene_per_day', 10);
        $key = $this->sceneVoiceKey($scene);

        return (int) Cache::get($key, 0) < $limit;
    }

    public function recordSceneVoiceRegeneration(Scene $scene): void
    {
        $key = $this->sceneVoiceKey($scene);
        $this->incrementKey($key);
    }

    public function canRegenerateEpisode(Episode $episode): bool
    {
        $limit = config('regeneration.max_episode_per_project_per_day', 5);
        $key = $this->episodeKey($episode);

        return (int) Cache::get($key, 0) < $limit;
    }

    public function recordEpisodeRegeneration(Episode $episode): void
    {
        $key = $this->episodeKey($episode);
        $this->incrementKey($key);
    }

    public function limitReachedMessage(string $type): string
    {
        return match ($type) {
            'scene_image' => 'Daily limit for scene image regeneration reached. Try again tomorrow.',
            'scene_voice' => 'Daily limit for scene voice regeneration reached. Try again tomorrow.',
            'episode' => 'Daily limit for episode regeneration reached. Try again tomorrow.',
            default => 'Regeneration limit reached. Try again later.',
        };
    }

    private function sceneImageKey(Scene $scene): string
    {
        $date = now()->format('Y-m-d');

        return "regeneration:scene_image:{$scene->id}:{$date}";
    }

    private function sceneVoiceKey(Scene $scene): string
    {
        $date = now()->format('Y-m-d');

        return "regeneration:scene_voice:{$scene->id}:{$date}";
    }

    private function episodeKey(Episode $episode): string
    {
        $projectId = $episode->project_id ?? $episode->project?->id;
        $date = now()->format('Y-m-d');

        return "regeneration:episode:{$projectId}:{$date}";
    }

    private function incrementKey(string $key): void
    {
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, self::TTL_SECONDS);
    }
}
