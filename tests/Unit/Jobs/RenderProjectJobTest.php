<?php

namespace Tests\Unit\Jobs;

use App\Enums\ProjectStatus;
use App\Jobs\RenderProjectJob;
use App\Models\Project;
use App\Models\RenderLog;
use App\Models\User;
use App\Services\VideoRenderService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class RenderProjectJobTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_updates_render_log_and_project_status_on_success(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P', 'status' => ProjectStatus::Ready]);
        $renderLog = RenderLog::create(['project_id' => $project->id, 'status' => 'pending']);

        $mockRenderService = Mockery::mock(VideoRenderService::class);
        $mockRenderService->shouldReceive('render')
            ->once()
            ->andReturn(['output_url' => 'http://example.com/video.mp4', 'thumbnail_url' => 'http://example.com/thumb.jpg']);

        $job = new RenderProjectJob($project, $renderLog, []);
        $job->handle($mockRenderService);

        $renderLog->refresh();
        $project->refresh();
        $this->assertSame('completed', $renderLog->status);
        $this->assertNotNull($renderLog->started_at);
        $this->assertNotNull($renderLog->completed_at);
        $this->assertSame(ProjectStatus::Completed, $project->status);
    }

    public function test_handle_does_nothing_when_render_log_mismatch(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P', 'status' => ProjectStatus::Ready]);
        $otherProject = Project::create(['user_id' => $user->id, 'title' => 'P2', 'status' => ProjectStatus::Ready]);
        $renderLog = RenderLog::create(['project_id' => $otherProject->id, 'status' => 'pending']);

        $mockRenderService = Mockery::mock(VideoRenderService::class);
        $mockRenderService->shouldNotReceive('render');

        $job = new RenderProjectJob($project, $renderLog, []);
        $job->handle($mockRenderService);

        $renderLog->refresh();
        $project->refresh();
        $this->assertSame('pending', $renderLog->status);
        $this->assertSame(ProjectStatus::Ready, $project->status);
    }
}
