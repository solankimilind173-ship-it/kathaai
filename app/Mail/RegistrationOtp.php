<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtp extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $otp,
        public string $name = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your verification code – '.config('app.name'),
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-otp'
        );
    }
}
