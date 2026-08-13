<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * L'argent est encaissé et l'abonnement est ouvert.
 *
 * ÉMIS APRÈS LA TRANSACTION, JAMAIS DEDANS. Émis à l'intérieur, un listener
 * lisant la base pourrait travailler sur un état non encore validé, et un
 * échec d'envoi ferait annuler l'encaissement lui-même. L'argent reçu ne se
 * défait pas parce qu'un e-mail n'est pas parti.
 */
class PaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payment $payment) {}
}
