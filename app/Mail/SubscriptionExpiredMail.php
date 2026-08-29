<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * L'abonnement est échu : le lien public ne répond plus.
 *
 * SEUL E-MAIL DU PRODUIT QUI ANNONCE UNE PERTE. Il doit donc être exact au mot
 * près, et exact veut dire rassurant ici sans être complaisant :
 *
 *   · la carte n'est PAS supprimée, les données restent en base ;
 *   · le SLUG est conservé — le lien déjà imprimé sur des cartes physiques
 *     redeviendra valable à la réactivation, aucune réimpression n'est
 *     nécessaire. C'est le point le plus important pour quelqu'un qui a
 *     commandé cinq cents cartes.
 *
 * Ce que le message ne fait pas : culpabiliser, ni employer « suspendu » ou
 * « bloqué », qui suggèrent une sanction là où il n'y a qu'une date passée.
 */
class SubscriptionExpiredMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $echeance,
        public string $renewUrl,
        public ?string $publicUrl = null,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.abonnement_expire.sujet'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.subscription-expired',
            text: 'emails.client.subscription-expired_text',
        );
    }
}
