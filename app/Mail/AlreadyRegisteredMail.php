<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Cas 2 — une inscription est tentée avec une adresse DÉJÀ titulaire d'un compte.
 *
 * Aucun compte n'est créé et l'écran affiché est identique à une inscription
 * normale : la différenciation n'existe QUE dans cet e-mail, que seul le
 * propriétaire réel de la boîte peut lire.
 */
class AlreadyRegisteredMail extends BaseMailable
{
    public function __construct(
        public string $loginUrl,
        public string $resetUrl,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.deja_inscrit.sujet', ['marque' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration.already-registered',
            text: 'emails.registration.already-registered_text',
        );
    }
}
