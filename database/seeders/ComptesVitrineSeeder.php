<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * LES COMPTES VITRINE — des cartes qui ne s'éteignent jamais.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CE SEEDER EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * Une carte de démonstration doit être vivante EN PERMANENCE. C'est elle
 * qu'on scanne devant un prospect, qu'on met dans une présentation, et qui
 * alimente « Voir un exemple » sur la page d'accueil.
 *
 * Or son abonnement expire comme celui de n'importe quel client — et sans
 * passerelle de paiement en production, il n'existe alors AUCUN moyen de la
 * rallumer autrement qu'à la main, dans l'administration. La démonstration
 * meurt donc toute seule, en silence, et on ne s'en aperçoit que devant le
 * prospect.
 *
 * C'est exactement ce qui est arrivé : l'essai gratuit du compte du
 * propriétaire a expiré, sa carte s'est éteinte, et il a fallu des heures
 * pour la rallumer.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CE SEEDER N'EST PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Ce n'est pas une porte dérobée. Il ne touche QUE des adresses écrites
 * explicitement dans `DEMO_ACCOUNT_EMAILS`, exactement comme `ADMIN_EMAIL`
 * désigne les administrateurs. Sans cette variable, il ne fait rien.
 *
 * Il n'invente AUCUN paiement : l'abonnement est posé sur la formule
 * gratuite. Le chiffre d'affaires ne bouge pas d'un franc, et les écrans de
 * statistiques comptent ces comptes pour ce qu'ils sont — des essais, pas
 * des ventes.
 *
 * Il ne crée aucun compte non plus : si l'adresse n'existe pas, il le dit et
 * passe. On ne fabrique pas de faux clients en production.
 */
class ComptesVitrineSeeder extends Seeder
{
    /**
     * Renouvelée à chaque démarrage, donc jamais réellement atteinte.
     *
     * Une échéance lointaine plutôt qu'un abonnement sans terme : le produit
     * sait déjà raisonner sur une date, et un `ends_at` nul se promène dans
     * tous les écrans comme un cas particulier.
     */
    private const JOURS = 365;

    public function run(): void
    {
        $adresses = collect(explode(',', (string) config('vitrine.emails')))
            ->map(fn (string $adresse) => mb_strtolower(trim($adresse)))
            ->filter()
            ->unique();

        if ($adresses->isEmpty()) {
            return;   // rien de déclaré : ce seeder n'a rien à faire
        }

        $plan = Plan::where('slug', 'essai-gratuit')->first();

        if (! $plan) {
            $this->command?->warn('Comptes vitrine : formule « essai-gratuit » absente, rien fait.');

            return;
        }

        $traites = 0;

        foreach ($adresses as $adresse) {
            if ($this->assurer($adresse, $plan)) {
                $traites++;
            }
        }

        $this->command?->info("Comptes vitrine : {$traites} carte(s) garantie(s) en ligne.");
    }

    /** Garantit qu'une adresse a un abonnement actif et une carte publiée. */
    private function assurer(string $adresse, Plan $plan): bool
    {
        $user = User::where('email', $adresse)->first();

        if (! $user) {
            $this->command?->line("  ? inconnu  : {$adresse} (aucun compte, ignoré)");

            return false;
        }

        /*
         | On PROLONGE l'abonnement existant plutôt que d'en empiler un
         | nouveau à chaque démarrage. Sans cela, un an de déploiements
         | laisserait des centaines de lignes sur le même compte, et les
         | écrans d'abonnements deviendraient illisibles.
         */
        $abonnement = $user->subscriptions()->latest('id')->first();

        if ($abonnement) {
            $abonnement->forceFill([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => $abonnement->starts_at ?? now(),
                'ends_at' => now()->addDays(self::JOURS),
            ])->save();
        } else {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addDays(self::JOURS),
                'status' => Subscription::STATUS_ACTIVE,
            ]);
        }

        /*
         | LA CARTE EST AUSSI PUBLIÉE. Un abonnement actif ne suffit pas :
         | `isPubliclyVisible()` exige les deux. Une vitrine dont l'abonnement
         | court mais dont la carte reste en brouillon serait invisible — le
         | même piège, déguisé.
         |
         | Une carte SUSPENDUE par l'administration n'est pas republiée : cette
         | décision-là vient d'un humain et doit le rester.
         */
        $profil = $user->profile;

        if ($profil && ! $profil->isDeactivated() && ! $profil->is_active) {
            $profil->forceFill(['is_active' => true])->save();
        }

        $this->command?->line("  = vitrine  : {$adresse} (carte en ligne, échéance dans ".self::JOURS.' jours)');

        Log::info('Compte vitrine garanti en ligne.', ['email' => $adresse]);

        return true;
    }
}
