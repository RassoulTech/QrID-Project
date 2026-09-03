<?php

namespace App\Console\Commands;

use App\Models\ProfileEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * L'AGRÉGATION NOCTURNE — et la purge qui la suit.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE EST REJOUABLE, ET C'EST SA PROPRIÉTÉ LA PLUS IMPORTANTE
 * ═══════════════════════════════════════════════════════════════════════
 * Une nuit manquée, un conteneur redémarré au mauvais moment, une reprise
 * manuelle sur trente jours : tout cela arrive. L'écriture se fait donc en
 * UPSERT sur (profile_id, jour) — repasser deux fois sur le même jour donne
 * exactement le même résultat.
 *
 * Sans cette propriété, une commande relancée doublerait les compteurs sans
 * que rien ne le signale, et personne ne saurait dire quel chiffre est juste.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE NE TRAITE QUE LA VEILLE, PAR DÉFAUT
 * ═══════════════════════════════════════════════════════════════════════
 * Le jour EN COURS n'est jamais agrégé : il n'est pas terminé, et un agrégat
 * partiel écrasé chaque heure vaut moins qu'une lecture directe. Les pages
 * lisent donc les agrégats pour l'historique ET la table source pour
 * aujourd'hui — quelques centaines de lignes, bornées par la date du jour.
 *
 *   php artisan app:agreger-statistiques              la veille
 *   php artisan app:agreger-statistiques --jours=30   reprise sur 30 jours
 *   php artisan app:agreger-statistiques --purger     agrège puis purge
 */
class AgregerStatistiques extends Command
{
    protected $signature = 'app:agreger-statistiques
        {--jours=1 : Nombre de jours à agréger, en remontant depuis hier}
        {--purger : Supprime les événements bruts au-delà de la rétention}';

    protected $description = 'Agrège les événements de profil en compteurs journaliers, et purge les bruts.';

    public function handle(): int
    {
        $jours = max(1, (int) $this->option('jours'));

        $this->info("Agrégation sur {$jours} jour(s), en remontant depuis hier.");

        $lignes = 0;

        for ($i = 1; $i <= $jours; $i++) {
            $lignes += $this->agregerLeJour(Carbon::today()->subDays($i));
        }

        $this->info("  {$lignes} agrégat(s) écrit(s).");

        if ($this->option('purger')) {
            $this->purger();
        }

        return self::SUCCESS;
    }

    /**
     * Un jour, une requête d'agrégation, un upsert.
     *
     * TOUT SE PASSE EN SQL. Charger les événements en PHP pour les compter
     * ferait entrer en mémoire ce que la table d'agrégats existe justement
     * pour ne plus avoir à lire.
     */
    private function agregerLeJour(Carbon $jour): int
    {
        $debut = $jour->copy()->startOfDay();
        $fin = $jour->copy()->endOfDay();

        $agregats = ProfileEvent::query()
            ->whereBetween('created_at', [$debut, $fin])
            ->groupBy('profile_id')
            ->selectRaw('profile_id')
            ->selectRaw('SUM(type = ?) as vues', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(type = ?) as scans', [ProfileEvent::TYPE_SCAN])
            ->selectRaw('SUM(type = ?) as saves', [ProfileEvent::TYPE_SAVE])
            ->selectRaw('SUM(type = ?) as partages', [ProfileEvent::TYPE_SHARE])

            /*
             | `total` RESTE LA SOMME DES TROIS PREMIERS, et ce n'est pas un
             | détail d'arithmétique.
             |
             | Il valait COUNT(*) — ce qui donnait le même nombre tant que
             | seuls trois types existaient. L'arrivée des partages y aurait
             | ajouté silencieusement une quatrième catégorie : le « total »
             | affiché au client aurait augmenté du jour au lendemain sans
             | qu'aucune de ses trois tuiles ne bouge.
             |
             | Changer la signification d'un chiffre que quelqu'un regarde
             | depuis des mois est pire que de ne pas l'enrichir. Les partages
             | ont leur propre colonne.
             */
            ->selectRaw('SUM(type IN (?, ?, ?)) as total', [
                ProfileEvent::TYPE_VIEW, ProfileEvent::TYPE_SCAN, ProfileEvent::TYPE_SAVE,
            ])
            ->get();

        if ($agregats->isEmpty()) {
            return 0;
        }

        $maintenant = now();

        $lignes = $agregats->map(fn ($a) => [
            'profile_id' => $a->profile_id,
            'jour' => $jour->toDateString(),
            'vues' => (int) $a->vues,
            'scans' => (int) $a->scans,
            'saves' => (int) $a->saves,
            'partages' => (int) $a->partages,
            'total' => (int) $a->total,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ])->all();

        /*
         | PAR PAQUETS DE 500.
         |
         | Un upsert de dix mille lignes en une requête dépasse la taille de
         | paquet par défaut de MySQL, et l'erreur ne se produit qu'en
         | production, le jour où le volume la déclenche.
         */
        foreach (array_chunk($lignes, 500) as $paquet) {
            DB::table('profile_stats_daily')->upsert(
                $paquet,
                ['profile_id', 'jour'],
                ['vues', 'scans', 'saves', 'total', 'updated_at']
            );
        }

        return count($lignes);
    }

    /**
     * LA PURGE NE PART QUE DE CE QUI EST DÉJÀ AGRÉGÉ.
     *
     * La rétention est un réglage, pas une constante : douze mois par
     * défaut, parce que c'est la durée sur laquelle un client compare une
     * année à la précédente. Le jour où la loi ou le produit demandera
     * autre chose, ce sera une variable d'environnement à changer, pas un
     * déploiement.
     *
     * ELLE SUPPRIME PAR PAQUETS. Un DELETE sur plusieurs millions de lignes
     * tient un verrou pendant toute sa durée : les scans de la nuit
     * attendraient, et un scan qui attend est un visiteur qui ferme la page.
     */
    private function purger(): void
    {
        $mois = (int) config('statistiques.retention_mois', 12);
        $limite = Carbon::today()->subMonths($mois);

        $this->info("Purge des événements antérieurs au {$limite->toDateString()}.");

        $total = 0;

        do {
            $supprimes = ProfileEvent::query()
                ->where('created_at', '<', $limite)
                ->limit(2000)
                ->delete();

            $total += $supprimes;
        } while ($supprimes > 0);

        $this->info("  {$total} événement(s) brut(s) supprimé(s).");
    }
}
