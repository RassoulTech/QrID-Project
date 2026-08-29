<?php

namespace App\Services\Admin;

use App\Models\AdminAction;
use App\Models\Subscription;
use App\Models\User;
use App\Support\AdminActionType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Prolongation manuelle d'un abonnement — geste commercial, incident client.
 *
 * LE POINT DÉLICAT est la date de départ. Prolonger de 30 jours à partir
 * d'aujourd'hui sur un abonnement qui court encore lui ferait PERDRE les
 * jours restants. On prolonge donc depuis la fin en cours quand elle est
 * dans le futur, depuis maintenant quand elle est dépassée.
 *
 * Un abonnement expiré repasse en actif : c'est le sens d'une prolongation
 * accordée après coup.
 */
class SubscriptionExtensionService
{
    /** Bornes du formulaire. Au-delà, c'est un abonnement, pas un geste. */
    public const JOURS_MIN = 1;

    public const JOURS_MAX = 365;

    public function prolonger(User $client, int $jours, string $motif): Subscription
    {
        $abonnement = $this->abonnementAProlonger($client);

        if ($abonnement === null) {
            throw new RuntimeException(__('admin.flash.aucun_abonnement_a_prolonger'));
        }

        return DB::transaction(function () use ($abonnement, $client, $jours, $motif) {
            $depart = $abonnement->ends_at !== null && $abonnement->ends_at->isFuture()
                ? $abonnement->ends_at
                : now();

            $abonnement->forceFill([
                'ends_at' => $depart->copy()->addDays($jours),
                'status' => Subscription::STATUS_ACTIVE,
            ])->save();

            // Le cache d'abonnement actif porté par le modèle User deviendrait
            // faux : la fiche client afficherait l'ancienne date juste après
            // l'enregistrement.
            $client->forgetActiveSubscription();

            AdminAction::log(
                AdminActionType::PROLONGATION_ABONNEMENT,
                $abonnement,
                "+{$jours} jour(s) — {$motif}"
            );

            return $abonnement;
        });
    }

    /**
     * L'abonnement actif s'il y en a un, sinon le plus récent.
     *
     * Un client dont l'abonnement vient d'expirer est précisément celui qu'on
     * prolonge le plus souvent : se limiter aux abonnements actifs rendrait la
     * fonction inutilisable dans son cas principal.
     */
    public function abonnementAProlonger(User $client): ?Subscription
    {
        return $client->activeSubscription()
            ?? $client->subscriptions()->latest('id')->first();
    }
}
