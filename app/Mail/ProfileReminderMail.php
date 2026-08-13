<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Rappel : la carte est remplie mais jamais mise en ligne.
 *
 * DEUX ENVOIS, PAS UN DE PLUS — à 24 h puis à 72 h. Au-delà, un troisième
 * message ne convainc personne et fait basculer l'expéditeur dans les
 * indésirables ; ce coût-là se paierait ensuite sur les e-mails qui comptent
 * vraiment, comme la réinitialisation de mot de passe.
 *
 * Le ton diffère entre les deux : le premier suppose un oubli, le second
 * suppose un obstacle et propose de l'aide. Répéter le même texte à
 * l'identique serait la façon la plus sûre de ne pas être lu la seconde fois.
 */
class ProfileReminderMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $activateUrl,
        /** 1 = rappel de 24 h, 2 = rappel de 72 h. */
        public int $rang,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->rang === 1
                ? 'Votre carte est prête à être publiée'
                : 'Un obstacle pour publier votre carte ?',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.profile-reminder',
            text: 'emails.client.profile-reminder_text',
        );
    }
}
