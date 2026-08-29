<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Échéance qui approche — J-7, J-3, J-1, puis le jour même.
 *
 * QUATRE ENVOIS, ET C'EST BEAUCOUP. Ce qui les rend acceptables, c'est que le
 * sujet CHANGE à chaque palier : quatre fois « Votre abonnement expire
 * bientôt » se regroupent dans un fil que personne ne rouvre, tandis que
 * « demain » et « aujourd'hui » se lisent. L'urgence doit être dans la ligne
 * de sujet, là où elle est vue, pas dans le corps du message.
 *
 * Le message dit ce qui se passe à l'échéance — le lien public cesse de
 * répondre — et ce qui ne se passe pas : rien n'est supprimé.
 */
class SubscriptionExpiringMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public int $joursRestants,
        public string $echeance,
        public string $formule,
        public string $renewUrl,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: match (true) {
            $this->joursRestants <= 0 => __('emails.abonnement_expirant.sujet_aujourdhui'),
            $this->joursRestants === 1 => __('emails.abonnement_expirant.sujet_demain'),
            default => __('emails.abonnement_expirant.sujet'),
        });
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.subscription-expiring',
            text: 'emails.client.subscription-expiring_text',
        );
    }
}
