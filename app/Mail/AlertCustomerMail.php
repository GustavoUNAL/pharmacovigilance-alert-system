<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public string $warningMessage,
        public string $medicationLotNumber,
        public string $recommendationMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Important safety notice regarding your medication'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.alert-customer',
        );
    }
}
