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

    /**
     * Au-delà, aucun worker ne consomme la file.
     *
     * Dix minutes : un worker sain prend une tâche en quelques secondes. Ce
     * seuil laisse passer un redémarrage de conteneur sans crier, et attrape
     * une file abandonnée avant qu'un client ne s'en aperçoive.
     */
    private const SEUIL_ATTENTE_MINUTES = 10;

    public function handle(): int
    {
        $alertes = 0;

        $this->newLine();
        $this->line('  <fg=white;options=bold>SANTÉ DE L\'APPLICATION</>');
        $this->line('  environnement : '.app()->environment());
        $this->newLine();

        $alertes += $this->base();
        $alertes += $this->connexions();
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

    /**
     * LA LIMITE DE CONNEXIONS, LUE DEPUIS LA BASE ELLE-MÊME.
     *
     * ═══════════════════════════════════════════════════════════════════
     * ON NE LA DEMANDE PLUS À L'HÉBERGEUR
     * ═══════════════════════════════════════════════════════════════════
     * Le plan Aiven fixe un plafond de connexions simultanées. Il figure
     * dans leur console — c'est-à-dire dans un endroit que personne ne
     * consulte au moment où le problème survient, et que le code ne peut
     * pas lire.
     *
     * Or MySQL le connaît : `max_connections` est une variable serveur, et
     * `Max_used_connections` retient le pic depuis le dernier redémarrage.
     * Deux requêtes suffisent donc à répondre à la question sans qu'aucune
     * valeur soit recopiée à la main quelque part — et une valeur recopiée
     * à la main est une valeur qui devient fausse au premier changement de
     * plan.
     *
     * LE SEUIL EST À 80 % DU PIC. En dessous, on a de la marge ; au-dessus,
     * la prochaine pointe de trafic rendra des « Too many connections », et
     * ce message-là arrive toujours au pire moment.
     */
    private function connexions(): int
    {
        try {
            $max = (int) DB::selectOne('SHOW VARIABLES LIKE "max_connections"')->Value;
            $actuelles = (int) DB::selectOne('SHOW STATUS LIKE "Threads_connected"')->Value;
            $pic = (int) DB::selectOne('SHOW STATUS LIKE "Max_used_connections"')->Value;
        } catch (\Throwable) {
            $this->ligne('Connexions', 'illisibles sur ce serveur', false);

            return 1;
        }

        $part = $max > 0 ? round($pic / $max * 100) : 0;
        $sain = $part < 80;

        $this->ligne(
            'Connexions',
            "{$actuelles} ouverte(s), pic {$pic} sur {$max} ({$part} % du plafond)",
            $sain
        );

        return $sain ? 0 : 1;
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

        /*
         | ═══════════════════════════════════════════════════════════════
         | L'ÂGE DE LA PLUS VIEILLE TÂCHE — LE VRAI SIGNAL
         | ═══════════════════════════════════════════════════════════════
         | Le NOMBRE de tâches en attente ne dit rien : zéro tâche peut
         | signifier « tout va bien » comme « rien n'arrive jamais », et
         | cinquante tâches peuvent être une pointe normale.
         |
         | L'ÂGE, lui, ne ment pas. Si la plus ancienne attend depuis dix
         | minutes, c'est qu'aucun worker ne la prend — et c'est exactement
         | la panne qui a déjà coûté deux parcours au produit : les e-mails
         | de confirmation partaient dans la table `jobs` et n'en sortaient
         | jamais, SANS AUCUNE ERREUR nulle part.
         |
         | Ce contrôle attrape ce silence en dix minutes au lieu de le
         | laisser passer jusqu'à cinq cents tâches.
         */
        $plusVieille = DB::table('jobs')->min('available_at');
        $attenteMinutes = $plusVieille
            ? (int) round((now()->timestamp - (int) $plusVieille) / 60)
            : 0;

        $fileSaine = $plusVieille === null || $attenteMinutes < self::SEUIL_ATTENTE_MINUTES;

        $this->ligne(
            'Attente la plus longue',
            $plusVieille
                ? "{$attenteMinutes} min (seuil ".self::SEUIL_ATTENTE_MINUTES.' min)'
                : 'file vide',
            $fileSaine
        );

        if (! $fileSaine) {
            $this->line('       <fg=yellow>Aucun worker ne semble consommer la file. Vérifier le service qrid-worker.</>');
        }

        return ($enAttente < self::SEUIL_FILE ? 0 : 1)
            + ($echoues === 0 ? 0 : 1)
            + ($fileSaine ? 0 : 1);
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
