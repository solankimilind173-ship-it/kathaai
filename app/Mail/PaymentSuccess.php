<?php

namespace App\Mail;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Plan $plan,
        public string $amountFormatted,
        public string $currency,
        public string $interval,
        public ?string $invoiceNumber = null,
        public ?string $invoicePdfUrl = null,
        public ?string $receiptUrl = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment successful – Your '.$this->plan->name.' plan – '.config('app.name'),
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-success'
        );
    }
}
