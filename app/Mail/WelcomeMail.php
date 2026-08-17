<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Compte confirmé : l'essai gratuit vient de s'ouvrir.
 *
 * IL NE FÉLICITE PAS, IL ORIENTE. Quelqu'un qui vient de confirmer son adresse
 * n'a encore aucune carte : c'est le moment exact où il faut lui dire quoi
 * faire, pas lui souhaiter la bienvenue. Le message porte donc une seule
 * action, et la date de fin d'essai en clair — « 15 jours » se compte mal, une
 * date se retient.
 */
class WelcomeMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $createUrl,
        public int $trialDays,
        public ?string $trialEndsAt = null,

        /**
         * Lien du groupe WhatsApp réservé aux clients.
         *
         * Null quand il n'est pas configuré : le paragraphe disparaît alors
         * entièrement, plutôt que de proposer un lien mort. Un client qui
         * clique sur une invitation périmée conclut que le groupe n'existe
         * pas, ou pire, qu'il n'y a pas été accepté.
         */
        public ?string $groupeUrl = null,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte est actif — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.welcome',
            text: 'emails.client.welcome_text',
        );
    }
}
