<?php

namespace App\Services;

use App\Mail\PasswordChanged;
use App\Mail\ProjectStepCompleted;
use App\Mail\WelcomeRegistered;
use App\Models\NotificationLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send welcome email on registration and log it.
     */
    public function sendWelcomeEmail(User $user): void
    {
        if (! $user->getPreference(User::PREF_EMAIL_WELCOME, true)) {
            return;
        }
        $recipient = $user->email;
        $subject = 'Welcome to '.config('app.name');

        try {
            Mail::to($recipient)->send(new WelcomeRegistered($user));
            $this->log('welcome', $user->id, $recipient, $subject, null, null, null);
        } catch (\Throwable $e) {
            Log::error('Welcome email failed: '.$e->getMessage(), ['user_id' => $user->id]);
        }
    }

    /**
     * Send password changed email with optional location and device ID; log it.
     */
    public function sendPasswordChangedEmail(User $user, ?string $location = null, ?string $deviceId = null): void
    {
        if (! $user->getPreference(User::PREF_EMAIL_PASSWORD_CHANGED, true)) {
            return;
        }
        $recipient = $user->email;
        $subject = 'Your password was changed – '.config('app.name');

        try {
            Mail::to($recipient)->send(new PasswordChanged($user, $location, $deviceId));
            $this->log('password_changed', $user->id, $recipient, $subject, null, null, [
                'location' => $location,
                'device_id' => $deviceId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Password changed email failed: '.$e->getMessage(), ['user_id' => $user->id]);
        }
    }

    /**
     * Send project step completed email and log it.
     */
    public function sendProjectStepCompleted(User $user, Project $project, string $stepName, string $stepDescription): void
    {
        if (! $user->getPreference(User::PREF_EMAIL_PROJECT_STEP, true)) {
            return;
        }
        $recipient = $user->email;
        $subject = 'Project step completed: '.$stepName.' – '.$project->title;

        try {
            Mail::to($recipient)->send(new ProjectStepCompleted($user, $project, $stepName, $stepDescription));
            $this->log('project_step', $user->id, $recipient, $subject, $stepName, $project->id, null);
        } catch (\Throwable $e) {
            Log::error('Project step email failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'step' => $stepName,
            ]);
        }
    }

    private function log(
        string $type,
        ?int $userId,
        string $recipient,
        string $subject,
        ?string $stepName,
        ?int $projectId,
        ?array $meta
    ): void {
        NotificationLog::create([
            'type' => $type,
            'user_id' => $userId,
            'recipient' => $recipient,
            'subject' => $subject,
            'step_name' => $stepName,
            'project_id' => $projectId,
            'meta' => $meta,
            'sent_at' => now(),
        ]);
    }
}
