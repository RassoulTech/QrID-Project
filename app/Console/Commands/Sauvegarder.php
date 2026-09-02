<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * LA SAUVEGARDE DE LA BASE — hors de chez l'hébergeur de la base.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI LES SAUVEGARDES D'AIVEN NE SUFFISENT PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Aiven sauvegarde, et le fait bien. Mais ces sauvegardes vivent CHEZ AIVEN,
 * et partagent donc le sort du compte : une facture impayée, une suspension,
 * une erreur de leur côté, et la base ET ses copies disparaissent ensemble.
 *
 * Une sauvegarde qui vit au même endroit que la donnée n'est pas une
 * sauvegarde : c'est une copie.
 *
 * Ce dump-ci part sur le disque de l'application — donc, en production, sur
 * le stockage objet. Deux hébergeurs distincts, deux comptes distincts, deux
 * factures distinctes.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE NE VAUT QUE SI ELLE EST RESTAURÉE
 * ═══════════════════════════════════════════════════════════════════════
 * Une sauvegarde jamais restaurée est une hypothèse. La procédure de test
 * et le journal des restaurations sont dans docs/ENVIRONNEMENTS.md, et ce
 * tableau doit être daté tous les trimestres.
 *
 *     php artisan app:sauvegarder
 *     php artisan app:sauvegarder --garder=8
 */
class Sauvegarder extends Command
{
    protected $signature = 'app:sauvegarder
        {--garder=8 : Nombre de sauvegardes à conserver}';

    protected $description = 'Produit un dump de la base et le dépose sur le disque applicatif.';

    public function handle(): int
    {
        $config = config('database.connections.'.config('database.default'));

        if (($config['driver'] ?? null) !== 'mysql') {
            $this->error('Seul MySQL est pris en charge par cette commande.');

            return self::FAILURE;
        }

        $nom = 'sauvegardes/qrid-'.now()->format('Y-m-d-His').'.sql';

        $this->info('Sauvegarde en cours…');

        /*
         | LE MOT DE PASSE PASSE PAR L'ENVIRONNEMENT, jamais par la ligne de
         | commande. Un `--password=…` en argument est visible de tout le
         | système par `ps`, et se retrouve dans l'historique du shell.
         |
         | MYSQL_PWD est la variable que le client mysqldump lit de lui-même.
         */
        $processus = new Process([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',   // pas de verrou sur les tables
            '--quick',                // ligne à ligne : pas de table entière en mémoire
            '--default-character-set=utf8mb4',
            $config['database'],
        ], null, ['MYSQL_PWD' => (string) $config['password']], null, 600);

        $processus->run();

        if (! $processus->isSuccessful()) {
            $this->error('mysqldump a échoué : '.trim($processus->getErrorOutput()));

            return self::FAILURE;
        }

        $contenu = $processus->getOutput();

        if (strlen($contenu) < 1024) {
            // Un dump minuscule est un dump vide. Le déposer écraserait la
            // rotation avec un fichier inutile, et la découverte se ferait
            // le jour de la restauration.
            $this->error('Le dump est anormalement petit ('.strlen($contenu).' octets) : rien n\'a été déposé.');

            return self::FAILURE;
        }

        Storage::disk('local')->put($nom, $contenu);

        $this->info(sprintf('  %s — %s Mo', $nom, round(strlen($contenu) / 1048576, 1)));

        $this->rotation((int) $this->option('garder'));

        $this->avertirSiLeDisqueEstEphemere();

        return self::SUCCESS;
    }

    /**
     * UNE SAUVEGARDE QUI DISPARAÎT AU PROCHAIN DÉPLOIEMENT N'EN EST PAS UNE.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CET AVERTISSEMENT EXISTE
     * ═══════════════════════════════════════════════════════════════════
     * Le fichier est déposé sur le disque `local`, c'est-à-dire le disque
     * du conteneur. Sur Render, ce disque est ÉPHÉMÈRE : il est recréé à
     * chaque mise en ligne. Une sauvegarde hebdomadaire y survit donc
     * jusqu'au prochain déploiement, et pas au-delà.
     *
     * La commande réussit — elle a bien produit un dump valide — mais son
     * succès serait trompeur si personne ne disait où il atterrit. Une
     * sauvegarde en laquelle on croit à tort est pire que pas de
     * sauvegarde du tout : elle fait renoncer à en chercher une vraie.
     *
     * Aiven conserve par ailleurs ses propres sauvegardes quotidiennes.
     * Celle-ci est le filet du jour où le compte Aiven lui-même devient
     * inaccessible — et c'est précisément ce jour-là qu'un fichier resté
     * dans le conteneur ne servira à rien.
     *
     * L'avertissement disparaîtra de lui-même le jour où FILESYSTEM_DISK
     * pointera vers un stockage objet.
     */
    private function avertirSiLeDisqueEstEphemere(): void
    {
        if (config('filesystems.default') !== 'local' || ! app()->environment('production')) {
            return;
        }

        $message = 'Sauvegarde déposée sur le disque du conteneur, qui est '
            .'recréé à chaque déploiement : elle ne lui survivra pas. '
            .'Configurez un stockage objet (FILESYSTEM_DISK) pour lui donner '
            .'une valeur réelle.';

        $this->warn('  '.$message);

        Log::warning('Sauvegarde sur disque éphémère', ['fichier' => 'sauvegardes/']);
    }

    /**
     * LA ROTATION GARDE LES N PLUS RÉCENTES.
     *
     * Huit par défaut : deux mois de sauvegardes hebdomadaires. Assez pour
     * remonter avant une corruption découverte tardivement, assez peu pour
     * que le stockage reste négligeable.
     *
     * Elle s'exécute APRÈS le dépôt réussi. Supprimer d'abord laisserait,
     * en cas d'échec du nouveau dump, une sauvegarde de moins qu'avant.
     */
    private function rotation(int $garder): void
    {
        $fichiers = collect(Storage::disk('local')->files('sauvegardes'))
            ->filter(fn (string $f) => str_ends_with($f, '.sql'))
            ->sortDesc()
            ->values();

        $aSupprimer = $fichiers->slice(max(1, $garder));

        foreach ($aSupprimer as $fichier) {
            Storage::disk('local')->delete($fichier);
            $this->line("  retirée : {$fichier}");
        }
    }
}
