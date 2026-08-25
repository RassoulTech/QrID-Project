<?php

namespace App\Console\Commands;

use App\Models\MailLog;
use App\Models\ProfileEvent;
use App\Models\ProfileStatDaily;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * L'ÉTAT DE SANTÉ, EN UNE SORTIE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * À QUOI ELLE SERT VRAIMENT
 * ═══════════════════════════════════════════════════════════════════════
 * Quand quelque chose ne va pas en production, la première question est
 * toujours la même : par où commencer ? Sans réponse, on ouvre le tableau
 * de bord de l'hébergeur, on lit des journaux, on essaie une page, et on
 * perd vingt minutes avant de savoir si c'est la base, la file ou le disque.
 *
 * Cette commande répond en une fois. Elle ne répare rien : elle dit où
 * regarder.
 *
 *     php artisan app:health
 *
 * CHAQUE MESURE PORTE SON SEUIL. Un chiffre sans seuil n'informe personne :
 * « 412 000 événements » ne veut rien dire tant qu'on ignore qu'on
 * s'inquiète à partir de cinq millions.
 */
class Sante extends Command
{
    protected $signature = 'app:health';

    protected $description = "Affiche l'état de la base, des files, du stockage et des envois.";

    /** Au-delà, la table d'événements dépasse ce qu'un petit plan absorbe. */
    private const SEUIL_EVENEMENTS = 5_000_000;

    /** Une file qui dépasse ce seuil n'est plus en retard : elle est bloquée. */
    private const SEUIL_FILE = 500;

    public function handle(): int
    {
        $alertes = 0;

        $this->newLine();
        $this->line('  <fg=white;options=bold>SANTÉ DE L\'APPLICATION</>');
        $this->line('  environnement : '.app()->environment());
        $this->newLine();

        $alertes += $this->base();
        $alertes += $this->tables();
        $alertes += $this->files();
        $alertes += $this->stockage();
        $alertes += $this->courrier();

        $this->newLine();

        if ($alertes > 0) {
            $this->error("  {$alertes} point(s) d'attention.");

            // Code de sortie non nul : une surveillance externe peut s'y fier.
            return self::FAILURE;
        }

        $this->info('  Tout est au vert.');

        return self::SUCCESS;
    }

    private function base(): int
    {
        try {
            $debut = microtime(true);
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $debut) * 1000, 1);

            $this->ligne('Base de données', "connectée en {$ms} ms", $ms < 200);

            return $ms < 200 ? 0 : 1;
        } catch (\Throwable $e) {
            $this->ligne('Base de données', 'INJOIGNABLE — '.$e->getMessage(), false);

            return 1;
        }
    }

    private function tables(): int
    {
        $evenements = ProfileEvent::count();
        $agregats = ProfileStatDaily::count();

        $sain = $evenements < self::SEUIL_EVENEMENTS;

        $this->ligne(
            'profile_events',
            number_format($evenements, 0, ',', ' ').' lignes (seuil '.number_format(self::SEUIL_EVENEMENTS, 0, ',', ' ').')',
            $sain
        );

        $this->ligne('profile_stats_daily', number_format($agregats, 0, ',', ' ').' agrégats', true);

        /*
         | L'AGRÉGATION A-T-ELLE TOURNÉ ? La question compte plus que le
         | nombre de lignes : une table d'agrégats qui cesse d'être alimentée
         | laisse les statistiques figées sans qu'aucune erreur n'apparaisse.
         */
        $dernier = ProfileStatDaily::max('jour');
        // absolute et arrondi : Carbon rend un flottant signé, et « il y a
        // -2 j » ne veut rien dire pour qui lit un état de santé.
        $retard = $dernier ? (int) round(now()->startOfDay()->diffInDays($dernier, absolute: true)) : null;

        $this->ligne(
            'Dernière agrégation',
            $dernier ? "{$dernier} (il y a {$retard} j)" : 'JAMAIS EXÉCUTÉE',
            $dernier !== null && $retard <= 2
        );

        return ($sain ? 0 : 1) + ($dernier !== null && $retard <= 2 ? 0 : 1);
    }

    private function files(): int
    {
        try {
            $enAttente = DB::table('jobs')->count();
            $echoues = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $this->ligne('Files', 'tables absentes', false);

            return 1;
        }

        $this->ligne('File en attente', "{$enAttente} tâche(s) (seuil ".self::SEUIL_FILE.')', $enAttente < self::SEUIL_FILE);
        $this->ligne('Tâches échouées', (string) $echoues, $echoues === 0);

        return ($enAttente < self::SEUIL_FILE ? 0 : 1) + ($echoues === 0 ? 0 : 1);
    }

    private function stockage(): int
    {
        try {
            $disque = Storage::disk('public');
            $sonde = 'sante-'.uniqid().'.txt';

            $disque->put($sonde, 'ok');
            $lisible = $disque->get($sonde) === 'ok';
            $disque->delete($sonde);

            $this->ligne('Stockage public', $lisible ? 'écriture et lecture' : 'ÉCRITURE IMPOSSIBLE', $lisible);

            return $lisible ? 0 : 1;
        } catch (\Throwable $e) {
            $this->ligne('Stockage public', 'ERREUR — '.$e->getMessage(), false);

            return 1;
        }
    }

    private function courrier(): int
    {
        try {
            $dernier = MailLog::where('status', 'sent')->latest('sent_at')->first();
        } catch (\Throwable) {
            $this->ligne('Dernier envoi', 'journal indisponible', false);

            return 1;
        }

        if (! $dernier) {
            $this->ligne('Dernier envoi', 'aucun envoi réussi enregistré', false);

            return 1;
        }

        $heures = (int) round($dernier->sent_at?->diffInHours(now(), absolute: true) ?? 999);

        $this->ligne('Dernier envoi', $dernier->sent_at?->format('d/m/Y H:i')." (il y a {$heures} h)", true);

        return 0;
    }

    private function ligne(string $quoi, string $valeur, bool $sain): void
    {
        $marque = $sain ? '<fg=green>OK  </>' : '<fg=red>!!  </>';

        $this->line(sprintf('  %s %-22s %s', $marque, $quoi, $valeur));
    }
}
