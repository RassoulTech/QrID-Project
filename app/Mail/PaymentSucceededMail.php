<?php

namespace App\Mail;

use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Reçu de paiement. Le seul e-mail du produit qui puisse servir de PREUVE.
 *
 * Il porte donc la référence de l'opérateur, le montant, le moyen et la date —
 * c'est ce qu'on rouvrira six mois plus tard devant une contestation, et ce
 * qu'on citera dans un échange avec l'agrégateur. Rien d'approximatif, aucun
 * montant recalculé à l'affichage.
 *
 * PIÈCES JOINTES — le QR Code et le PDF d'impression.
 * Elles sont reçues en OCTETS, déjà produites par le listener, et non
 * fabriquées ici. La raison est nette : générer un PDF peut échouer, et cet
 * échec ne doit jamais empêcher le reçu de partir. Le client a payé ; il lui
 * faut sa preuve, même sans le fichier d'impression. Le listener peut donc
 * passer null sans que ce message cesse d'être valable.
 */
class PaymentSucceededMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $reference,
        public string $montant,
        public string $moyen,
        public string $formule,
        public string $date,
        public ?string $echeance,
        public ?string $publicUrl,
        public string $dashboardUrl,
        private ?string $qrPng = null,
        private ?string $pdf = null,
        private string $slug = 'carte',
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement confirmé — '.$this->montant.' FCFA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.payment-succeeded',
            text: 'emails.client.payment-succeeded_text',
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $pieces = [];

        if ($this->qrPng !== null) {
            $pieces[] = Attachment::fromData(fn () => $this->qrPng, 'qr-'.$this->slug.'.png')
                ->withMime('image/png');
        }

        if ($this->pdf !== null) {
            $pieces[] = Attachment::fromData(fn () => $this->pdf, 'carte-'.$this->slug.'.pdf')
                ->withMime('application/pdf');
        }

        return $pieces;
    }
}
