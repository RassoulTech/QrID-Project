<?php

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Une échéance approche. Émis par la commande planifiée, jamais par une
 * requête HTTP : personne ne doit avoir à visiter le site pour être prévenu
 * que son abonnement se termine.
 *
 * $joursRestants vaut 7, 3, 1 ou 0 — les quatre paliers du plan. Il est
 * transporté plutôt que recalculé par le listener : le calcul appartient à
 * celui qui a sélectionné les abonnements, et deux calculs indépendants du
 * même nombre finissent toujours par diverger d'un jour à minuit.
 */
class SubscriptionExpiring
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public int $joursRestants,
    ) {}
}
