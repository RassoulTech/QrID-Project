<?php

namespace App\Console\Commands;

use App\Support\Design\Analyseur;
use Illuminate\Console\Command;

/**
 * LE RELEVÉ BRUT — ce qui produit `audit-design.json`.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI ELLE ENTRE DANS LE DÉPÔT
 * ═══════════════════════════════════════════════════════════════════════
 * Le fichier `audit-design.json` existait ; le script qui l'avait produit,
 * non. Un chiffre dont on ne peut pas refaire le calcul n'est pas une
 * mesure, c'est une affirmation. Trois relevés successifs ont donné 1963,
 * 651 puis 1598 — impossible de savoir lequel croire sans pouvoir relancer
 * le compte.
 *
 *     php artisan design:audit
 *     php artisan design:audit --sortie=audit-design.json
 *
 * La commande partage son moteur avec `design:check` : les deux ne peuvent
 * donc pas diverger, ce qui était le vrai risque.
 */
class DesignAudit extends Command
{
    protected $signature = 'design:audit
                            {--sortie=audit-design.json : Le fichier JSON à écrire}
                            {--sans-fichier : Affiche seulement le résumé}';

    protected $description = 'Produit le relevé complet des valeurs en dur.';

    public function handle(Analyseur $analyseur): int
    {
        $resultat = $analyseur->analyser(base_path());
        $constats = $resultat['constats'];

        // ── Regroupement par fichier, comme le veut le format d'audit ──
        $parFichier = [];

        foreach ($constats as $categorie => $items) {
            foreach ($items as $item) {
                $parFichier[$item['fichier']][] = [
                    'ligne' => $item['ligne'],
                    'categorie' => $categorie,
                    'valeur' => $item['valeur'],
                ];
            }
        }

        foreach ($parFichier as &$items) {
            usort($items, fn ($a, $b) => $a['ligne'] <=> $b['ligne']);
        }
        unset($items);

        uasort($parFichier, fn ($a, $b) => count($b) <=> count($a));

        $this->newLine();
        $this->line('  <fg=white;options=bold>RELEVÉ DES VALEURS EN DUR</>');
        $this->line(sprintf('  %d occurrence(s) dans %d fichier(s)',
            $resultat['total'], count($parFichier)));
        $this->newLine();

        $this->line('  <options=bold>PAR CATÉGORIE</>');
        foreach ($constats as $categorie => $items) {
            $this->line(sprintf('     %-18s %5d', $categorie, count($items)));
        }

        $this->newLine();
        $this->line('  <options=bold>PAR FICHIER</> — les vingt plus fournis');
        foreach (array_slice($parFichier, 0, 20, true) as $fichier => $items) {
            $this->line(sprintf('     %-52s %5d', $fichier, count($items)));
        }

        if (! $this->option('sans-fichier')) {
            $chemin = base_path((string) $this->option('sortie'));

            file_put_contents($chemin, (string) json_encode([
                'genere_le' => now()->toIso8601String(),
                'total' => $resultat['total'],
                'par_categorie' => array_map('count', $constats),
                'par_fichier' => $parFichier,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $this->newLine();
            $this->info(sprintf('  Écrit dans %s', $this->option('sortie')));
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
