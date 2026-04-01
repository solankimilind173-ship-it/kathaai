<?php

namespace Tests\Unit\Services;

use App\Mail\WelcomeRegistered;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use DatabaseMigrations;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->service = new NotificationService;
    }

    public function test_send_welcome_email_sends_when_preference_true(): void
    {
        $user = User::factory()->create(['preferences' => null]);
        $this->service->sendWelcomeEmail($user);
        Mail::assertSent(WelcomeRegistered::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_send_welcome_email_skips_when_preference_false(): void
    {
        $user = User::factory()->create([
            'preferences' => [\App\Models\User::PREF_EMAIL_WELCOME => false],
        ]);
        $this->service->sendWelcomeEmail($user);
        Mail::assertNotSent(WelcomeRegistered::class);
    }

    public function test_send_project_step_completed_sends_when_preference_true(): void
    {
        $user = User::factory()->create(['preferences' => null]);
        $project = Project::create(['user_id' => $user->id, 'title' => 'My Project']);
        $this->service->sendProjectStepCompleted($user, $project, 'Scenes', 'Scenes generated');
        Mail::assertSent(\App\Mail\ProjectStepCompleted::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_send_project_step_completed_skips_when_preference_false(): void
    {
        $user = User::factory()->create([
            'preferences' => [\App\Models\User::PREF_EMAIL_PROJECT_STEP => false],
        ]);
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        $this->service->sendProjectStepCompleted($user, $project, 'Step', 'Desc');
        Mail::assertNotSent(\App\Mail\ProjectStepCompleted::class);
    }
}
