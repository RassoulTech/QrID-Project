<?php

namespace App\Services\Payment;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Events\ProfilePublished;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Souscription et renouvellement — l'argent d'un côté, l'accès de l'autre.
 *
 * Toute la logique d'encaissement vit ici : les contrôleurs ne font
 * qu'orienter. Le montant vient TOUJOURS de la formule en base, jamais du
 * formulaire — sinon le prix se négocierait depuis le navigateur.
 */
class CheckoutService
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Ouvre un paiement en attente. Rien n'est accordé à ce stade.
     *
     * Le Payment est écrit AVANT tout appel à l'opérateur : si celui-ci
     * répond mal, ou pas du tout, la trace existe et la somme réclamée reste
     * vérifiable. L'inverse — appeler puis enregistrer — perd les paiements
     * dont la réponse se perd en route.
     */
    public function start(User $user, Plan $plan, string $method): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'subscription_id' => null,
            'provider' => $this->gateway->name(),
            'method' => $method,
            'amount_fcfa' => $plan->price_fcfa,   // la formule fait foi, pas le formulaire
            'status' => Payment::STATUS_PENDING,
            'payload' => ['plan_slug' => $plan->slug],
        ]);
    }

    /** L'URL de l'opérateur où envoyer l'utilisateur. */
    public function redirectUrl(Payment $payment): string
    {
        return $this->gateway->initiate($payment);
    }

    /**
     * Peut-on encaisser ici ? À demander AVANT start().
     *
     * Dans l'autre ordre, chaque tentative laisse un Payment « pending » que
     * personne ne confirmera jamais — et ce sont exactement ceux-là qui
     * déclenchent l'alerte « paiement en attente depuis plus d'une heure »
     * du récapitulatif du soir. Une passerelle absente finirait par se
     * signaler comme une panne d'encaissement.
     */
    public function estDisponible(): bool
    {
        return $this->gateway->estDisponible();
    }

    /**
     * Encaissement confirmé : abonnement ouvert ou prolongé, profil publié.
     *
     * TOUT tient dans une transaction. Un paiement marqué réussi sans
     * abonnement en face serait le pire des états : le client a payé et n'a
     * rien, et rien dans les données ne dit pourquoi.
     */
    public function succeed(Payment $payment, array $callback = []): bool
    {
        if ($payment->isSuccessful()) {
            return true;   // idempotence : un retour rejoué ne double rien
        }

        if (! $this->gateway->confirms($payment, $callback)) {
            $this->fail($payment, 'retour non confirmé par la passerelle');

            return false;
        }

        $plan = Plan::where('slug', $payment->payload['plan_slug'] ?? '')->first();

        if (! $plan) {
            $this->fail($payment, 'formule introuvable');

            return false;
        }

        // Vrai si la publication a lieu MAINTENANT, du fait de ce paiement.
        $publieMaintenant = false;

        DB::transaction(function () use ($payment, $plan, &$publieMaintenant) {
            $user = $payment->user;

            $abonnement = $this->openOrExtend($user, $plan);

            $payment->forceFill([
                'subscription_id' => $abonnement->id,
                'status' => Payment::STATUS_SUCCESS,
            ])->save();

            // Le profil devient public. C'est l'aboutissement du parcours :
            // payer sans être publié n'aurait aucun sens pour le client.
            $profile = $user->profile;

            if ($profile && ! $profile->is_active) {
                $profile->forceFill(['is_active' => true])->save();
                $publieMaintenant = true;
            }
        });

        Log::info('Paiement encaissé.', [
            'payment_id' => $payment->id,
            'plan' => $plan->slug,
            'amount_fcfa' => $payment->amount_fcfa,
        ]);

        /*
         |----------------------------------------------------------------------
         | LES ÉVÉNEMENTS SONT ÉMIS APRÈS LA TRANSACTION, JAMAIS DEDANS
         |----------------------------------------------------------------------
         | Deux raisons, et la seconde est la plus grave.
         |
         | 1. Un listener qui relit la base à l'intérieur de la transaction
         |    travaillerait sur un état non encore validé.
         |
         | 2. Surtout : un envoi d'e-mail qui lève une exception à l'intérieur
         |    ferait ANNULER L'ENCAISSEMENT. Le client serait débité par
         |    l'opérateur, et sans abonnement chez nous — l'état le plus
         |    coûteux qu'on puisse produire. L'argent reçu ne se défait pas
         |    parce qu'un message n'est pas parti.
         |
         | Un renouvellement n'émet PAS ProfilePublished : la carte était déjà
         | en ligne, et « votre carte est en ligne » à quelqu'un qui vient de
         | renouveler ne dit rien. Le reçu de paiement, lui, part toujours.
         */
        $payment->refresh();

        event(new PaymentSucceeded($payment));

        if ($publieMaintenant && $payment->user?->profile) {
            event(new ProfilePublished($payment->user->profile));
        }

        return true;
    }

    /** Échec ou annulation. Aucune donnée détruite : le client peut réessayer. */
    public function fail(Payment $payment, string $raison): void
    {
        if ($payment->isSuccessful()) {
            return;
        }

        // Un paiement déjà marqué en échec ne le redevient pas : sans cette
        // garde, un retour d'opérateur rejoué renverrait une seconde fois
        // « votre paiement n'a pas abouti » pour le même incident.
        $etaitDejaEnEchec = $payment->status === Payment::STATUS_FAILED;

        $payment->forceFill([
            'status' => Payment::STATUS_FAILED,
            'payload' => array_merge($payment->payload ?? [], ['raison' => $raison]),
        ])->save();

        Log::warning('Paiement non abouti.', [
            'payment_id' => $payment->id,
            'raison' => $raison,
        ]);

        if (! $etaitDejaEnEchec) {
            event(new PaymentFailed($payment, $raison));
        }
    }

    /**
     * Ouvre un abonnement, ou PROLONGE celui en cours.
     *
     * Un renouvellement anticipé ne doit jamais raccourcir ce qui reste dû :
     * on repart de la fin de l'abonnement courant, pas de maintenant. Un
     * abonnement expiré, lui, repart de maintenant.
     */
    private function openOrExtend(User $user, Plan $plan): Subscription
    {
        $courant = $user->activeSubscription();

        // La valeur mémorisée sur le modèle va devenir fausse dans un instant :
        // on l'oublie tout de suite, pour que rien ne lise l'ancien état après
        // l'écriture qui suit.
        $user->forgetActiveSubscription();

        // L'essai gratuit ne se prolonge pas : il est remplacé par la formule
        // payante, sans quoi le client paierait pour du temps déjà offert.
        if ($courant && ! $courant->isTrial()) {
            $depart = $courant->ends_at && $courant->ends_at->isFuture()
                ? $courant->ends_at
                : now();

            $courant->forceFill([
                'plan_id' => $plan->id,
                'ends_at' => $depart->copy()->addDays($plan->duration_days),
                'status' => Subscription::STATUS_ACTIVE,
            ])->save();

            return $courant;
        }

        $courant?->forceFill(['status' => Subscription::STATUS_CANCELLED])->save();

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration_days),
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }
}
