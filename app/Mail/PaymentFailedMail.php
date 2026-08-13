<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Paiement non abouti.
 *
 * LA PREMIÈRE PHRASE DOIT DIRE QU'AUCUNE SOMME N'A ÉTÉ PRÉLEVÉE. C'est la
 * seule question que se pose quelqu'un dont le paiement a échoué, et tant
 * qu'elle n'a pas de réponse il ne lit pas la suite. Un paiement en échec ne
 * débite jamais : le Payment reste `failed` et aucun abonnement n'est ouvert.
 *
 * LA RAISON TECHNIQUE N'APPARAÎT NULLE PART. « retour non confirmé par la
 * passerelle » n'aide personne, inquiète tout le monde, et donne à un tiers
 * une information sur notre infrastructure. Elle reste dans le journal et dans
 * l'alerte administrateur.
 */
class PaymentFailedMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $montant,
        public string $formule,
        public string $retryUrl,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre paiement n\'a pas abouti',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.payment-failed',
            text: 'emails.client.payment-failed_text',
        );
    }
}
