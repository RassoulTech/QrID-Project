<?php

namespace App\Listeners;

use App\Events\SubscriptionExpired;
use App\Mail\SubscriptionExpiredMail;
use App\Support\Courrier;

/**
 * L'abonnement est échu : la carte n'est plus consultable.
 *
 * L'adresse publique est reprise dans le message alors même qu'elle ne répond
 * plus. Ce n'est pas une négligence : c'est la preuve, sous les yeux du
 * client, que son lien n'a pas changé. Quelqu'un qui a fait imprimer des
 * cartes a besoin de lire cette adresse-là, identique, pour savoir que sa
 * commande n'est pas perdue.
 */
class AnnounceSubscriptionExpired
{
    public function handle(SubscriptionExpired $event): void
    {
        $abonnement = $event->subscription;
        $user = $abonnement->user;

        if (! $user) {
            return;
        }

        $profile = $user->profile;

        Courrier::informer($user->email, new SubscriptionExpiredMail(
            name: $user->name,
            echeance: $abonnement->ends_at?->translatedFormat('j F Y') ?? '',
            renewUrl: route('abonnement.paiement'),
            publicUrl: $profile ? route('profile.public', $profile->slug) : null,
            recipient: $user->email,
        ));
    }
}
