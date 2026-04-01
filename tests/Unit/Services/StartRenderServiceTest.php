<?php

namespace Tests\Unit\Services;

use App\Models\Episode;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scene;
use App\Models\User;
use App\Services\CreditCalculator;
use App\Services\CreditService;
use App\Services\OpenAIService;
use App\Services\StartRenderService;
use App\Services\VideoMetadataService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StartRenderServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_can_generate_trailer_for_eligible_plan_and_long_project(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
            'allow_trailer_generation' => true,
        ]);
        $user->plan_id = $plan->id;
        $user->save();

        $project = Project::create(['user_id' => $user->id, 'title' => 'Test Project', 'language' => 'english', 'status' => 'draft']);

        $episode = Episode::create(['project_id' => $project->id, 'title' => 'E1']);
        for ($i = 0; $i < 12; $i++) {
            Scene::create(['episode_id' => $episode->id, 'title' => 'Scene', 'description' => 'desc', 'scene_number' => $i + 1, 'duration' => 400]);
        }

        $openAi = Mockery::mock(OpenAIService::class);
        $openAi->shouldReceive('generateVideoMetadata')->andReturn(['title' => 'Test', 'description' => 'desc', 'hashtags' => ['#test']]);
        $service = new StartRenderService(new CreditCalculator(), new CreditService(), new VideoMetadataService($openAi));

        $this->assertTrue($service->canGenerateTrailer($project, $plan));
    }

    public function test_cannot_generate_trailer_for_short_project_or_no_plan_flag(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'is_active' => true,
            'allow_trailer_generation' => false,
        ]);
        $user->plan_id = $plan->id;
        $user->save();

        $project = Project::create(['user_id' => $user->id, 'title' => 'Test Project', 'language' => 'english', 'status' => 'draft']);

        $episode = Episode::create(['project_id' => $project->id, 'title' => 'E1']);
        Scene::create(['episode_id' => $episode->id, 'title' => 'Scene', 'description' => 'desc', 'scene_number' => 1, 'duration' => 200]);

        $openAi = Mockery::mock(OpenAIService::class);
        $openAi->shouldReceive('generateVideoMetadata')->andReturn(['title' => 'Test', 'description' => 'desc', 'hashtags' => ['#test']]);
        $service = new StartRenderService(new CreditCalculator(), new CreditService(), new VideoMetadataService($openAi));

        $this->assertFalse($service->canGenerateTrailer($project, $plan));

        $plan->allow_trailer_generation = true;
        $plan->save();

        $this->assertFalse($service->canGenerateTrailer($project, $plan));
    }
}
