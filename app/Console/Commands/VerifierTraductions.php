<?php

namespace App\Console\Commands;

use App\Support\Langue;
use Illuminate\Console\Command;

/**
 * LES DEUX LANGUES DOIVENT PORTER EXACTEMENT LES MÊMES CLÉS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CETTE COMMANDE EMPÊCHE
 * ═══════════════════════════════════════════════════════════════════════
 * Une clé absente d'un fichier de langue ne casse rien. Laravel rend alors
 * la CLÉ ELLE-MÊME : l'écran affiche « dashboard.carte.titre » à la place
 * d'un titre, la page reste à 200, aucun test ne tombe.
 *
 * C'est le défaut le plus discret de tout ce chantier, et le plus sûr de
 * revenir : il suffit d'ajouter une phrase en français et d'oublier
 * l'anglais. Personne ne s'en aperçoit tant que personne ne bascule.
 *
 *     php artisan lang:check
 *
 * Elle rend un code de sortie non nul dès qu'un écart existe — une
 * intégration continue peut s'y brancher sans lire la sortie.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE COMPARE AUSSI LES PLURIELS
 * ═══════════════════════════════════════════════════════════════════════
 * `:compte jour|:compte jours` porte deux formes, séparées par une barre.
 * Une traduction qui n'en donne qu'une fait échouer trans_choice au moment
 * où le nombre bascule — donc rarement, donc en production.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ET LES PARAMÈTRES
 * ═══════════════════════════════════════════════════════════════════════
 * `Bonjour :nom` traduit en `Hello :name` ne lève aucune erreur : la phrase
 * s'affiche avec « :name » écrit en toutes lettres au milieu. La commande
 * compare donc les jetons `:xxx` présents de part et d'autre.
 */
class VerifierTraductions extends Command
{
    protected $signature = 'lang:check
        {--diff : Affiche aussi les clés dont le texte est identique dans les deux langues}';

    protected $description = 'Compare les fichiers de langue et signale toute clé manquante ou incohérente.';

    public function handle(): int
    {
        $langues = Langue::disponibles();
        $reference = array_shift($langues);

        $tables = [];

        foreach (array_merge([$reference], $langues) as $code) {
            $tables[$code] = $this->aplatir($code);
        }

        $ecarts = 0;

        $this->newLine();
        $this->line('  <fg=white;options=bold>VÉRIFICATION DES TRADUCTIONS</>');
        $this->newLine();

        // ── Le compte par fichier, d'abord : c'est ce qu'on regarde ────
        $this->lignesParFichier($tables);

        foreach ($langues as $code) {
            $ecarts += $this->comparer($reference, $tables[$reference], $code, $tables[$code]);
            $ecarts += $this->comparer($code, $tables[$code], $reference, $tables[$reference]);
            $ecarts += $this->coherence($reference, $tables[$reference], $code, $tables[$code]);

            if ($this->option('diff')) {
                $this->identiques($reference, $tables[$reference], $code, $tables[$code]);
            }
        }

        $this->newLine();

        if ($ecarts > 0) {
            $this->error("  {$ecarts} écart(s). Les fichiers de langue ne concordent pas.");

            return self::FAILURE;
        }

        $this->info('  Aucun écart : toutes les langues portent les mêmes clés.');

        return self::SUCCESS;
    }

    /**
     * Toutes les clés d'une langue, aplaties en « fichier.chemin.vers.cle ».
     *
     * @return array<string, string>
     */
    private function aplatir(string $code): array
    {
        $racine = lang_path($code);

        if (! is_dir($racine)) {
            $this->warn("  Dossier absent : lang/{$code}");

            return [];
        }

        $plat = [];

        foreach (glob($racine.'/*.php') as $fichier) {
            $groupe = basename($fichier, '.php');
            $contenu = require $fichier;

            if (! is_array($contenu)) {
                continue;
            }

            $this->descendre($contenu, $groupe, $plat);
        }

        return $plat;
    }

    /**
     * @param  array<string, mixed>  $noeud
     * @param  array<string, string>  $plat
     */
    private function descendre(array $noeud, string $prefixe, array &$plat): void
    {
        foreach ($noeud as $cle => $valeur) {
            $chemin = $prefixe.'.'.$cle;

            if (is_array($valeur)) {
                $this->descendre($valeur, $chemin, $plat);

                continue;
            }

            $plat[$chemin] = (string) $valeur;
        }
    }

    /** @param  array<string, array<string, string>>  $tables */
    private function lignesParFichier(array $tables): void
    {
        $fichiers = [];

        foreach ($tables as $code => $plat) {
            foreach (array_keys($plat) as $cle) {
                $groupe = strstr($cle, '.', true) ?: $cle;
                $fichiers[$groupe][$code] = ($fichiers[$groupe][$code] ?? 0) + 1;
            }
        }

        ksort($fichiers);

        $codes = array_keys($tables);

        $lignes = [];

        foreach ($fichiers as $groupe => $comptes) {
            $ligne = [$groupe];

            foreach ($codes as $code) {
                $ligne[] = $comptes[$code] ?? 0;
            }

            $lignes[] = $ligne;
        }

        $totaux = ['<options=bold>TOTAL</>'];

        foreach ($codes as $code) {
            $totaux[] = '<options=bold>'.count($tables[$code]).'</>';
        }

        $lignes[] = $totaux;

        $this->table(array_merge(['Fichier'], $codes), $lignes);
    }

    /**
     * Les clés présentes dans $sourcePlat et absentes de $ciblePlat.
     *
     * @param  array<string, string>  $sourcePlat
     * @param  array<string, string>  $ciblePlat
     */
    private function comparer(string $source, array $sourcePlat, string $cible, array $ciblePlat): int
    {
        $manquantes = array_diff_key($sourcePlat, $ciblePlat);

        if ($manquantes === []) {
            return 0;
        }

        $this->newLine();
        $this->error(sprintf(
            '  %d clé(s) présente(s) dans « %s » et ABSENTE(S) de « %s » :',
            count($manquantes), $source, $cible
        ));

        foreach (array_keys($manquantes) as $cle) {
            $this->line("    <fg=red>manque</> lang/{$cible}/ → {$cle}");
        }

        return count($manquantes);
    }

    /**
     * Pluriels et paramètres : présents des deux côtés, mais incompatibles.
     *
     * @param  array<string, string>  $a
     * @param  array<string, string>  $b
     */
    private function coherence(string $codeA, array $a, string $codeB, array $b): int
    {
        $ecarts = 0;

        foreach (array_intersect_key($a, $b) as $cle => $texteA) {
            $texteB = $b[$cle];

            // ── Les formes plurielles ─────────────────────────────────
            $formesA = substr_count($texteA, '|');
            $formesB = substr_count($texteB, '|');

            if ($formesA !== $formesB) {
                $this->newLine();
                $this->error("  Pluriel incohérent — {$cle}");
                $this->line("    {$codeA} : ".($formesA + 1).' forme(s)');
                $this->line("    {$codeB} : ".($formesB + 1).' forme(s)');
                $ecarts++;
            }

            // ── Les paramètres ────────────────────────────────────────
            $jetonsA = $this->jetons($texteA);
            $jetonsB = $this->jetons($texteB);

            if ($jetonsA !== $jetonsB) {
                $this->newLine();
                $this->error("  Paramètres différents — {$cle}");
                $this->line("    {$codeA} : ".($jetonsA ? implode(', ', $jetonsA) : 'aucun'));
                $this->line("    {$codeB} : ".($jetonsB ? implode(', ', $jetonsB) : 'aucun'));
                $ecarts++;
            }
        }

        return $ecarts;
    }

    /**
     * Les jetons `:xxx` d'une chaîne, triés et dédoublonnés.
     *
     * `:compte` doit se retrouver des deux côtés ; l'ordre, non — l'anglais
     * ne place pas les mots comme le français, c'est tout l'intérêt.
     *
     * @return list<string>
     */
    private function jetons(string $texte): array
    {
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $texte, $trouves);

        $jetons = array_values(array_unique($trouves[1]));
        sort($jetons);

        return $jetons;
    }

    /**
     * Les clés dont le texte n'a pas bougé d'une langue à l'autre.
     *
     * Ce n'est PAS une erreur — « Contact », « Awa Ndiaye », « QrID » se
     * disent pareil. Mais c'est le meilleur endroit où chercher une
     * traduction oubliée par copier-coller.
     *
     * @param  array<string, string>  $a
     * @param  array<string, string>  $b
     */
    private function identiques(string $codeA, array $a, string $codeB, array $b): void
    {
        $memes = [];

        foreach (array_intersect_key($a, $b) as $cle => $texte) {
            if ($texte === $b[$cle] && trim($texte) !== '') {
                $memes[$cle] = $texte;
            }
        }

        if ($memes === []) {
            return;
        }

        $this->newLine();
        $this->warn(sprintf(
            '  %d clé(s) identique(s) entre « %s » et « %s » — à relire :',
            count($memes), $codeA, $codeB
        ));

        foreach ($memes as $cle => $texte) {
            $this->line('    '.$cle.'  <fg=gray>'.mb_substr($texte, 0, 50).'</>');
        }
    }
}
