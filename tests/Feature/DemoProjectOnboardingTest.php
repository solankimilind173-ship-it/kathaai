<?php

namespace Tests\Feature;

use App\Jobs\GenerateProjectStructureJob;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoProjectOnboardingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_first_project_is_created_for_free_for_a_new_user(): void
    {
        Queue::fake();
        Mail::fake();

        $user = User::factory()->create([
            'credits' => 0,
            'onboarding_status' => 'not_started',
        ]);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'source_type' => 'uploaded',
            'title' => 'My Demo Story',
            'story' => 'A young hero walks into a glowing city and discovers a hidden machine beneath the streets.',
            'video_minutes' => 5,
            'quality' => '1080p',
            'video_frame' => '16:9',
            'default_video_format' => 'youtube',
            'default_fps' => 24,
            'default_subtitle_style' => 'default',
        ]);

        $project = Project::first();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertNotNull($project);
        $this->assertSame(0, (int) $user->fresh()->credits);
        $this->assertSame(0, (int) $project->fresh()->total_credits_used);
        $this->assertSame('in_progress', $user->fresh()->onboarding_status);
        $this->assertSame('demo-project-created', $user->fresh()->last_onboarding_step);
        Queue::assertPushed(GenerateProjectStructureJob::class);
    }

    public function test_second_project_still_requires_scene_generation_credits(): void
    {
        Queue::fake();
        Mail::fake();

        $user = User::factory()->create([
            'credits' => 0,
        ]);

        Project::create([
            'user_id' => $user->id,
            'title' => 'Existing Project',
            'source_type' => 'uploaded',
            'story_source' => 'Existing story',
            'status' => 'draft',
            'language' => 'hindi',
            'quality' => '1080p',
            'video_minutes' => 5,
            'total_credits_used' => 0,
            'is_public' => false,
            'is_archived' => false,
        ]);

        $response = $this
            ->from(route('projects.create'))
            ->actingAs($user)
            ->post(route('projects.store'), [
                'source_type' => 'uploaded',
                'title' => 'Second Project',
                'story' => 'A second story that should require credits.',
                'video_minutes' => 5,
                'quality' => '1080p',
                'video_frame' => '16:9',
                'default_video_format' => 'youtube',
                'default_fps' => 24,
                'default_subtitle_style' => 'default',
            ]);

        $response->assertRedirect(route('projects.create'));
        $response->assertSessionHasErrors('credits');
        $this->assertSame(1, Project::count());
        Queue::assertNothingPushed();
    }
}
