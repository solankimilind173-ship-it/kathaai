<?php

namespace Tests\Unit\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Models\Episode;
use App\Models\Project;
use App\Models\Scene;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use DatabaseMigrations;

    private CreditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreditService;
    }

    public function test_scene_generation_cost_returns_config_value(): void
    {
        $this->assertSame(50, $this->service->sceneGenerationCost());
    }

    public function test_scene_image_regeneration_cost_returns_config_value(): void
    {
        $this->assertSame(15, $this->service->sceneImageRegenerationCost());
    }

    public function test_scene_voice_regeneration_cost_returns_config_value(): void
    {
        $this->assertSame(10, $this->service->sceneVoiceRegenerationCost());
    }

    public function test_has_enough_for_scene_generation_when_sufficient(): void
    {
        $user = User::factory()->create(['credits' => 100]);
        $this->assertTrue($this->service->hasEnoughForSceneGeneration($user));
    }

    public function test_has_enough_for_scene_generation_when_insufficient(): void
    {
        $user = User::factory()->create(['credits' => 10]);
        $this->assertFalse($this->service->hasEnoughForSceneGeneration($user));
    }

    public function test_has_enough_for_scene_image(): void
    {
        $userEnough = User::factory()->create(['credits' => 20]);
        $userLow = User::factory()->create(['credits' => 5]);
        $this->assertTrue($this->service->hasEnoughForSceneImage($userEnough));
        $this->assertFalse($this->service->hasEnoughForSceneImage($userLow));
    }

    public function test_has_enough_for_scene_voice(): void
    {
        $userEnough = User::factory()->create(['credits' => 15]);
        $userLow = User::factory()->create(['credits' => 5]);
        $this->assertTrue($this->service->hasEnoughForSceneVoice($userEnough));
        $this->assertFalse($this->service->hasEnoughForSceneVoice($userLow));
    }

    public function test_has_enough_credits(): void
    {
        $user = User::factory()->create(['credits' => 50]);
        $this->assertTrue($this->service->hasEnoughCredits($user, 50));
        $this->assertFalse($this->service->hasEnoughCredits($user, 51));
    }

    public function test_deduct_throws_when_insufficient_credits(): void
    {
        $user = User::factory()->create(['credits' => 5]);
        $project = Project::create(['user_id' => $user->id, 'title' => 'Test Project']);

        $this->expectException(InsufficientCreditsException::class);
        $this->expectExceptionMessage('50');

        $this->service->deduct($user, $project, 50, 'scene_generation');
    }

    public function test_deduct_reduces_user_credits_and_creates_transaction(): void
    {
        $user = User::factory()->create(['credits' => 100]);
        $project = Project::create(['user_id' => $user->id, 'title' => 'Test Project']);

        $this->service->deduct($user, $project, 30, 'scene_generation');

        $user->refresh();
        $this->assertSame(70, $user->credits);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'amount' => -30,
            'type' => 'usage',
            'feature' => 'scene_generation',
        ]);
    }

    public function test_deduct_increments_project_total_credits_used(): void
    {
        $user = User::factory()->create(['credits' => 100]);
        $project = Project::create([
            'user_id' => $user->id,
            'title' => 'Test Project',
            'total_credits_used' => 10,
        ]);

        $this->service->deduct($user, $project, 20, 'project_render');

        $project->refresh();
        $this->assertSame(30, $project->total_credits_used);
    }

    public function test_deduct_for_scene_image_deducts_and_increments_scene_credits_used(): void
    {
        $user = User::factory()->create(['credits' => 50]);
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $episode = Episode::create(['project_id' => $project->id, 'episode_number' => 1]);
        $scene = Scene::create(['episode_id' => $episode->id, 'description' => 'Scene', 'scene_number' => 1, 'credits_used' => 0]);

        $this->service->deductForSceneImage($user, $scene);

        $user->refresh();
        $scene->refresh();
        $this->assertSame(50 - 15, $user->credits);
        $this->assertSame(15, $scene->credits_used);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'scene_id' => $scene->id,
            'feature' => 'scene_image_regeneration',
        ]);
    }

    public function test_deduct_for_scene_voice_deducts_and_increments_scene_credits_used(): void
    {
        $user = User::factory()->create(['credits' => 50]);
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $episode = Episode::create(['project_id' => $project->id, 'episode_number' => 1]);
        $scene = Scene::create(['episode_id' => $episode->id, 'description' => 'Scene', 'scene_number' => 1, 'credits_used' => 0]);

        $this->service->deductForSceneVoice($user, $scene);

        $user->refresh();
        $scene->refresh();
        $this->assertSame(50 - 10, $user->credits);
        $this->assertSame(10, $scene->credits_used);
        $this->assertDatabaseHas('credit_transactions', [
            'feature' => 'scene_voice_regeneration',
        ]);
    }
}
