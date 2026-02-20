<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectStepCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Project $project,
        public string $stepName,
        public string $stepDescription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Project step completed: ' . $this->stepName . ' – ' . $this->project->title,
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-step-completed'
        );
    }
}
