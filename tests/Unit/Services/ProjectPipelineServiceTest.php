<?php

namespace Tests\Unit\Services;

use App\Enums\ProjectStatus;
use App\Jobs\GenerateProjectStructureJob;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Project;
use App\Models\RenderLog;
use App\Models\Scene;
use App\Models\User;
use App\Services\ProjectPipelineService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProjectPipelineServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_start_generation_sets_status_and_dispatches_structure_job(): void
    {
        Bus::fake();

        $project = Project::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Pipeline Story',
            'language' => 'english',
            'status' => ProjectStatus::Draft,
        ]);

        app(ProjectPipelineService::class)->startGeneration($project);

        $this->assertSame(ProjectStatus::Generating, $project->fresh()->status);
        Bus::assertDispatched(GenerateProjectStructureJob::class);
    }

    public function test_scene_media_readiness_waits_for_scene_generation_to_finish(): void
    {
        $project = Project::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Slow Story',
            'language' => 'english',
            'status' => ProjectStatus::Generating,
        ]);

        Episode::create([
            'project_id' => $project->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'summary' => 'Summary',
            'status' => 'generating_scenes',
        ]);

        $readiness = app(ProjectPipelineService::class)->sceneMediaReadiness($project->fresh());

        $this->assertFalse($readiness['ready']);
        $this->assertStringContainsString('Scene breakdown', $readiness['reason']);
    }

    public function test_summarize_and_sync_project_status_when_scene_media_is_ready(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'title' => 'Ready Story',
            'language' => 'english',
            'status' => ProjectStatus::Generating,
        ]);

        Character::create([
            'project_id' => $project->id,
            'name' => 'Aarav',
            'description' => 'Lead character',
            'image_path' => 'characters/1/aarav.png',
        ]);

        $episode = Episode::create([
            'project_id' => $project->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'summary' => 'Summary',
            'status' => 'scenes_generated',
        ]);

        Scene::create([
            'episode_id' => $episode->id,
            'title' => 'Scene 1',
            'description' => 'A dramatic opening',
            'scene_number' => 1,
            'image_url' => 'scenes/1/scene-1.png',
            'voice_url' => 'scenes/1/scene-1.mp3',
            'status' => 'ready',
        ]);

        RenderLog::create([
            'project_id' => $project->id,
            'status' => 'completed',
            'video_format' => 'youtube',
            'video_title' => 'Ready Story',
            'video_description' => 'Desc',
            'hashtags' => '#kathaai',
        ]);

        $service = app(ProjectPipelineService::class);
        $summary = $service->summarize($project->fresh());
        $status = $service->syncProjectStatus($project->fresh());

        $this->assertSame(ProjectStatus::Ready, $status);
        $this->assertSame(1, $summary['counts']['scenes_with_images']);
        $this->assertSame(1, $summary['counts']['scenes_with_voices']);
        $this->assertSame('completed', collect($summary['stages'])->firstWhere('key', 'media')['status']);
        $this->assertGreaterThan(0, $summary['progress_percentage']);
    }
}
