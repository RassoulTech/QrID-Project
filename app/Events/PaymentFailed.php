<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Paiement non abouti : refus de l'opérateur, abandon, ou retour non confirmé.
 *
 * La raison est portée par l'événement car elle est INTERNE. Elle sert au
 * journal et à l'alerte administrateur ; l'e-mail au client, lui, n'en
 * reprend rien. « retour non confirmé par la passerelle » n'aide personne et
 * inquiète tout le monde — le client a besoin de savoir qu'il n'a pas été
 * débité et comment réessayer, pas de notre vocabulaire technique.
 */
class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $raison = '',
    ) {}
}
