<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * La carte est en ligne. Le message porte le LIEN, et rien d'autre ne compte.
 *
 * Ce lien est ce que le client va copier-coller dans WhatsApp le jour même :
 * il apparaît deux fois — sur le bouton et en toutes lettres — pour être
 * sélectionnable au doigt sur un téléphone, y compris quand les styles sont
 * bloqués et que le bouton n'existe plus.
 */
class ProfilePublishedMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $publicUrl,
        public string $dashboardUrl,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre carte est en ligne — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.profile-published',
            text: 'emails.client.profile-published_text',
        );
    }
}
