<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmRegistrationMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $verifyUrl,
        public int $ttlMinutes,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.confirmation.sujet', ['marque' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration.confirm',
            text: 'emails.registration.confirm_text',
        );
    }
}
