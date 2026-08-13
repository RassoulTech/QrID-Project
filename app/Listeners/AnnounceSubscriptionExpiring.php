<?php

namespace App\Listeners;

use App\Events\SubscriptionExpiring;
use App\Mail\SubscriptionExpiringMail;
use App\Support\Courrier;

/**
 * Relance d'échéance — client uniquement.
 *
 * AUCUNE ALERTE À L'ÉQUIPE ICI, et c'est délibéré. Sur cent clients, cent
 * messages internes par palier, quatre paliers : quatre cents e-mails pour un
 * fait parfaitement normal. Le nombre d'abonnements arrivant à échéance se lit
 * d'un coup d'œil sur l'écran « Abonnements », et il figurera dans le
 * récapitulatif quotidien du bloc 2. Une alerte par client n'ajouterait rien
 * qu'un bruit qui rendrait les vraies alertes invisibles.
 */
class AnnounceSubscriptionExpiring
{
    public function handle(SubscriptionExpiring $event): void
    {
        $abonnement = $event->subscription;
        $user = $abonnement->user;

        if (! $user) {
            return;
        }

        Courrier::informer($user->email, new SubscriptionExpiringMail(
            name: $user->name,
            joursRestants: $event->joursRestants,
            echeance: $abonnement->ends_at?->translatedFormat('j F Y') ?? '',
            formule: $abonnement->plan?->name ?? 'en cours',
            renewUrl: route('abonnement.paiement'),
            recipient: $user->email,
        ));
    }
}
