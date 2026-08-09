<?php

namespace App\Services\Payment;

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

        DB::transaction(function () use ($payment, $plan) {
            $user = $payment->user;

            $abonnement = $this->openOrExtend($user, $plan);

            $payment->forceFill([
                'subscription_id' => $abonnement->id,
                'status' => Payment::STATUS_SUCCESS,
            ])->save();

            // Le profil devient public. C'est l'aboutissement du parcours :
            // payer sans être publié n'aurait aucun sens pour le client.
            $user->profile?->forceFill(['is_active' => true])->save();
        });

        Log::info('Paiement encaissé.', [
            'payment_id' => $payment->id,
            'plan' => $plan->slug,
            'amount_fcfa' => $payment->amount_fcfa,
        ]);

        return true;
    }

    /** Échec ou annulation. Aucune donnée détruite : le client peut réessayer. */
    public function fail(Payment $payment, string $raison): void
    {
        if ($payment->isSuccessful()) {
            return;
        }

        $payment->forceFill([
            'status' => Payment::STATUS_FAILED,
            'payload' => array_merge($payment->payload ?? [], ['raison' => $raison]),
        ])->save();

        Log::warning('Paiement non abouti.', [
            'payment_id' => $payment->id,
            'raison' => $raison,
        ]);
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
