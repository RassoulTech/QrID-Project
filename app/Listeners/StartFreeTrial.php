<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ouvre l'essai gratuit à la création du compte.
 *
 * La durée vient du plan « essai-gratuit » en base, jamais d'une constante :
 * changer la durée commerciale ne doit pas demander de redéploiement.
 */
class StartFreeTrial
{
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        // Idempotence : un compte ne reçoit qu'un seul essai.
        if ($user->subscriptions()->exists()) {
            return;
        }

        $plan = Plan::where('slug', 'essai-gratuit')->where('is_active', true)->first();

        if (! $plan) {
            Log::error('Essai gratuit impossible : plan « essai-gratuit » absent ou inactif.', [
                'user_id' => $user->id,
            ]);

            return;
        }

        DB::transaction(function () use ($user, $plan) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addDays($plan->duration_days),
                'status' => Subscription::STATUS_ACTIVE,
            ]);
        });

        // Le modèle mémorise son abonnement courant : sans cet oubli, tout ce
        // qui suit dans la même requête croirait le compte encore sans essai.
        $user->forgetActiveSubscription();

        Log::info('Essai gratuit ouvert', [
            'user_id' => $user->id,
            'days' => $plan->duration_days,
        ]);
    }
}
