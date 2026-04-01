<?php

namespace Tests\Unit\Services;

use App\Models\Episode;
use App\Models\Project;
use App\Models\Scene;
use App\Models\User;
use App\Services\RegenerationLimitService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RegenerationLimitServiceTest extends TestCase
{
    use DatabaseMigrations;

    private RegenerationLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new RegenerationLimitService;
    }

    public function test_can_regenerate_scene_image_when_under_limit(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $episode = Episode::create(['project_id' => $project->id, 'episode_number' => 1]);
        $scene = Scene::create(['episode_id' => $episode->id, 'description' => 'A scene', 'scene_number' => 1]);

        $this->assertTrue($this->service->canRegenerateSceneImage($scene));
    }

    public function test_record_scene_image_increments_and_can_hit_limit(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $episode = Episode::create(['project_id' => $project->id, 'episode_number' => 1]);
        $scene = Scene::create(['episode_id' => $episode->id, 'description' => 'A scene', 'scene_number' => 1]);

        $limit = config('regeneration.max_scene_image_per_scene_per_day', 10);
        for ($i = 0; $i < $limit; $i++) {
            $this->service->recordSceneImageRegeneration($scene);
        }
        $this->assertFalse($this->service->canRegenerateSceneImage($scene));
    }

    public function test_can_regenerate_scene_voice_when_under_limit(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $episode = Episode::create(['project_id' => $project->id, 'episode_number' => 1]);
        $scene = Scene::create(['episode_id' => $episode->id, 'description' => 'A scene', 'scene_number' => 1]);

        $this->assertTrue($this->service->canRegenerateSceneVoice($scene));
    }

    public function test_record_scene_voice_increments(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $episode = Episode::create(['project_id' => $project->id, 'episode_number' => 1]);
        $scene = Scene::create(['episode_id' => $episode->id, 'description' => 'A scene', 'scene_number' => 1]);

        $this->service->recordSceneVoiceRegeneration($scene);
        $this->service->recordSceneVoiceRegeneration($scene);
        $limit = config('regeneration.max_scene_voice_per_scene_per_day', 10);
        for ($i = 2; $i < $limit; $i++) {
            $this->service->recordSceneVoiceRegeneration($scene);
        }
        $this->assertFalse($this->service->canRegenerateSceneVoice($scene));
    }

    public function test_can_regenerate_episode_when_under_limit(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $episode = Episode::create(['project_id' => $project->id, 'episode_number' => 1]);

        $this->assertTrue($this->service->canRegenerateEpisode($episode));
    }

    public function test_limit_reached_message_for_each_type(): void
    {
        $this->assertStringContainsString('scene image', $this->service->limitReachedMessage('scene_image'));
        $this->assertStringContainsString('scene voice', $this->service->limitReachedMessage('scene_voice'));
        $this->assertStringContainsString('episode', $this->service->limitReachedMessage('episode'));
        $this->assertSame('Regeneration limit reached. Try again later.', $this->service->limitReachedMessage('unknown'));
    }
}
