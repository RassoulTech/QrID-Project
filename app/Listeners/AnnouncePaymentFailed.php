<?php

namespace App\Listeners;

use App\Enums\MotifAlerte;
use App\Events\PaymentFailed;
use App\Mail\PaymentFailedMail;
use App\Services\AdminNotifier;
use App\Support\Courrier;

/**
 * Paiement non abouti.
 *
 * DEUX MESSAGES QUI NE DISENT PAS LA MÊME CHOSE, et c'est voulu :
 *
 *   · au client — aucune somme prélevée, voici comment réessayer. Rien de
 *     technique ;
 *   · à l'équipe — la raison exacte, la référence, le moyen. C'est avec ces
 *     éléments qu'on ouvre un ticket chez l'agrégateur.
 *
 * Mettre la raison technique dans l'e-mail client inquiéterait sans aider, et
 * renseignerait un tiers sur notre infrastructure.
 */
class AnnouncePaymentFailed
{
    public function __construct(private AdminNotifier $equipe) {}

    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        $montant = number_format((int) $payment->amount_fcfa, 0, ',', ' ');
        $formule = $payment->subscription?->plan?->name
            ?? ($payment->payload['plan_slug'] ?? 'Abonnement');

        if ($user) {
            Courrier::informer($user->email, new PaymentFailedMail(
                name: $user->name,
                montant: $montant,
                formule: $formule,
                retryUrl: route('abonnement.paiement'),
                recipient: $user->email,
            ));
        }

        $this->equipe->alerter(
            MotifAlerte::PaiementEchoue,
            [
                'Client' => $user?->name ?? 'Compte supprimé',
                'Adresse' => $user?->email ?? '—',
                'Montant' => $montant.' FCFA',
                'Moyen' => $payment->method_label,
                'Formule' => $formule,
                'Référence' => $payment->provider_ref ?: 'PAY-'.$payment->id,
                'Raison' => $event->raison ?: 'non précisée',
            ],
            route('admin.payments.index'),
        );
    }
}
