<?php

namespace App\Console\Commands;

use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiring;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Échéances d'abonnement : les quatre relances, puis le constat d'expiration.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE FAIT DEUX CHOSES, ET LA SECONDE EST LA PLUS IMPORTANTE
 * ═══════════════════════════════════════════════════════════════════════
 * 1. Prévenir — J-7, J-3, J-1, puis le jour même.
 *
 * 2. CLORE les abonnements échus : passer leur statut à `expired`.
 *
 * Le second point n'est pas cosmétique. Sans lui, un abonnement dont la date
 * est passée reste `active` en base indéfiniment. scopeActive() le filtre
 * bien sur la date, donc le client est correctement hors ligne — mais les
 * écrans d'administration, eux, comptent des abonnements « actifs » qui ne le
 * sont plus. C'est ce chiffre-là qu'on regarde pour décider, et il serait faux.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * UN SEUL E-MAIL PAR JOUR ET PAR ABONNEMENT
 * ═══════════════════════════════════════════════════════════════════════
 * `notified_at` porte cette garantie. Sans elle, deux exécutions le même jour
 * — rattrapage après incident, planificateur relancé par un déploiement —
 * enverraient deux fois le même message.
 *
 * L'ORDRE DES PALIERS EST DÉCROISSANT (0 avant 1, 1 avant 3…). Un abonnement
 * ne peut correspondre qu'à un seul palier puisqu'ils portent sur des dates
 * distinctes ; l'ordre ne sert donc qu'à la lisibilité de la sortie, qui va du
 * plus urgent au moins urgent — c'est ce qu'on lit en premier dans un journal.
 */
class NotifySubscriptions extends Command
{
    protected $signature = 'subscriptions:notify {--dry-run : Affiche sans envoyer}';

    protected $description = 'Relance les abonnements arrivant à échéance (J-7, J-3, J-1, jour même) et clôt les échus.';

    /** Les quatre paliers du plan, du plus urgent au moins urgent. */
    private const PALIERS = [0, 1, 3, 7];

    public function handle(): int
    {
        $simulation = (bool) $this->option('dry-run');

        $relances = $this->relancer($simulation);
        $clos = $this->clore($simulation);

        Log::info('Relances d\'échéance', [
            'relances' => $relances,
            'expirations' => $clos,
            'simulation' => $simulation,
        ]);

        $this->info($simulation
            ? "Simulation — {$relances} relance(s) et {$clos} expiration(s) auraient été traitées."
            : "Relances envoyées : {$relances} · Abonnements clos : {$clos}");

        return self::SUCCESS;
    }

    /** Les quatre paliers d'avertissement. */
    private function relancer(bool $simulation): int
    {
        $total = 0;

        foreach (self::PALIERS as $jours) {
            $abonnements = Subscription::query()
                ->with(['user:id,name,email', 'plan:id,name'])
                ->expiringInDays($jours)
                ->where(fn ($q) => $q->whereNull('notified_at')
                    ->orWhereDate('notified_at', '<', now()->toDateString()))
                ->get();

            foreach ($abonnements as $abonnement) {
                if (! $abonnement->user) {
                    continue;
                }

                $this->line(sprintf(
                    '  J-%d · %s · %s',
                    $jours,
                    $abonnement->user->email,
                    $abonnement->ends_at?->toDateString() ?? '?'
                ));

                if ($simulation) {
                    $total++;

                    continue;
                }

                event(new SubscriptionExpiring($abonnement, $jours));

                $abonnement->forceFill(['notified_at' => now()])->save();

                $total++;
            }
        }

        return $total;
    }

    /**
     * Les abonnements dont la date est passée alors qu'ils se disent actifs.
     *
     * Le statut est écrit AVANT l'émission de l'événement. Si l'ordre était
     * inverse et qu'une erreur survenait pendant l'envoi, l'abonnement
     * resterait `active` et ressortirait demain : le client recevrait le même
     * « votre carte n'est plus consultable » chaque jour, indéfiniment.
     */
    private function clore(bool $simulation): int
    {
        $echus = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name'])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($echus as $abonnement) {
            $this->line('  échu · '.($abonnement->user?->email ?? 'compte supprimé'));

            if ($simulation) {
                continue;
            }

            $abonnement->forceFill(['status' => Subscription::STATUS_EXPIRED])->save();

            $abonnement->user?->forgetActiveSubscription();

            event(new SubscriptionExpired($abonnement));
        }

        return $echus->count();
    }
}
