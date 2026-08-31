<?php

namespace App\Console\Commands;

use App\Support\Design\Analyseur;
use Illuminate\Console\Command;

/**
 * LE GARDE-FOU DU SYSTÈME DE DESIGN.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI ELLE EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * `_tokens.scss` s'ouvre sur « LE SEUL FICHIER QUI CONTIENT DES VALEURS »
 * et annonce que cette commande signale les manquements. Elle n'existait
 * pas. Une règle sans garde-fou n'est pas une règle, c'est une intention —
 * et une intention se dégrade au premier correctif pressé.
 *
 *     php artisan design:check
 *     php artisan design:check --detail          # chaque occurrence
 *     php artisan design:check --categorie=couleur
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE CLIQUET
 * ═══════════════════════════════════════════════════════════════════════
 * La commande ne compare pas à zéro : elle compare au PLAFOND de
 * `config/design.php`. Comparer à zéro ferait échouer le dépôt dès
 * aujourd'hui et la commande serait désactivée dans la semaine.
 *
 * Le plafond ne remonte jamais. Chaque lot le baisse. La bonne réponse à
 * un dépassement est de corriger la valeur, pas le plafond.
 */
class DesignCheck extends Command
{
    protected $signature = 'design:check
                            {--detail : Affiche chaque occurrence, fichier et ligne}
                            {--categorie= : Ne montre qu\'une catégorie}
                            {--json : Sortie brute, pour l\'intégration continue}';

    protected $description = 'Signale les valeurs en dur et les entorses aux lois de design.';

    /** Les catégories qui composent le total « valeurs ». */
    private const VALEURS = ['couleur', 'longueur', 'duree'];

    public function handle(Analyseur $analyseur): int
    {
        $resultat = $analyseur->analyser(base_path());
        $constats = $resultat['constats'];

        $comptes = [];
        foreach ($constats as $categorie => $items) {
            $comptes[$categorie] = count($items);
        }

        $valeurs = array_sum(array_map(
            fn ($c) => $comptes[$c] ?? 0,
            self::VALEURS
        ));

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'valeurs' => $valeurs,
                'comptes' => $comptes,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $this->verdict($valeurs, $comptes, muet: true);
        }

        $this->newLine();
        $this->line('  <fg=white;options=bold>SYSTÈME DE DESIGN — CONFORMITÉ</>');
        $this->newLine();

        $this->tableau('valeurs', $valeurs, config('design.plafond.valeurs'),
            'couleurs, longueurs et durées hors _tokens.scss');

        foreach (['important', 'max-width', 'style-en-ligne', 'lien-mort', 'souligne'] as $c) {
            $this->tableau($c, $comptes[$c] ?? 0, config("design.plafond.$c"),
                $this->explication($c));
        }

        if ($this->option('detail') || $this->option('categorie')) {
            $this->detail($constats);
        }

        $this->repartition($constats);

        return $this->verdict($valeurs, $comptes);
    }

    private function tableau(string $nom, int $compte, ?int $plafond, string $note): void
    {
        $plafond ??= 0;
        $ok = $compte <= $plafond;
        $signe = $ok ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $marge = $plafond - $compte;

        $this->line(sprintf(
            '  %s  %-16s %5d   plafond %5d   %s',
            $signe,
            $nom,
            $compte,
            $plafond,
            $ok
                ? ($marge > 0 ? "<fg=green>−{$marge} sous le plafond</>" : '<fg=yellow>au plafond</>')
                : '<fg=red>DÉPASSEMENT de '.abs($marge).'</>'
        ));
        $this->line(sprintf('     <fg=gray>%s</>', $note));
    }

    private function explication(string $categorie): string
    {
        return match ($categorie) {
            'important' => 'loi 2 — le socle gagne par l\'ordre, pas par la surenchère',
            'max-width' => 'loi 3 — les @media ajoutent, ils ne défont pas',
            'style-en-ligne' => 'hors e-mails et PDF, où le style en ligne est imposé',
            'lien-mort' => 'un href="#" a l\'air cliquable et ne mène nulle part',
            'souligne' => 'loi 8 — la distinction passe par la couleur',
            default => '',
        };
    }

    /** @param array<string, list<array{fichier:string,ligne:int,valeur:string}>> $constats */
    private function repartition(array $constats): void
    {
        $parFichier = [];

        foreach (self::VALEURS as $categorie) {
            foreach ($constats[$categorie] ?? [] as $item) {
                $parFichier[$item['fichier']] = ($parFichier[$item['fichier']] ?? 0) + 1;
            }
        }

        if ($parFichier === []) {
            return;
        }

        arsort($parFichier);

        $this->newLine();
        $this->line('  <options=bold>LA CARTE DU CHANTIER</> — où sont les valeurs');
        $this->newLine();

        foreach (array_slice($parFichier, 0, 16, true) as $fichier => $n) {
            $this->line(sprintf('     %-52s %4d', $fichier, $n));
        }

        if (count($parFichier) > 16) {
            $this->line(sprintf('     <fg=gray>… et %d autre(s) fichier(s)</>',
                count($parFichier) - 16));
        }
    }

    /** @param array<string, list<array{fichier:string,ligne:int,valeur:string}>> $constats */
    private function detail(array $constats): void
    {
        $filtre = $this->option('categorie');

        foreach ($constats as $categorie => $items) {
            if ($filtre && $categorie !== $filtre) {
                continue;
            }

            $this->newLine();
            $this->line(sprintf('  <options=bold>%s</> — %d', strtoupper($categorie), count($items)));

            foreach (array_slice($items, 0, 60) as $item) {
                $this->line(sprintf('     %-46s %5d   %s',
                    $item['fichier'], $item['ligne'], $item['valeur']));
            }

            if (count($items) > 60) {
                $this->line(sprintf('     <fg=gray>… et %d autre(s)</>', count($items) - 60));
            }
        }
    }

    /** @param array<string,int> $comptes */
    private function verdict(int $valeurs, array $comptes, bool $muet = false): int
    {
        $depassements = [];

        if ($valeurs > (int) config('design.plafond.valeurs')) {
            $depassements[] = 'valeurs';
        }

        foreach (['important', 'max-width', 'style-en-ligne', 'lien-mort', 'souligne'] as $c) {
            if (($comptes[$c] ?? 0) > (int) config("design.plafond.$c")) {
                $depassements[] = $c;
            }
        }

        if ($muet) {
            return $depassements === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();

        if ($depassements !== []) {
            $this->error(sprintf('  Plafond dépassé : %s.', implode(', ', $depassements)));
            $this->line('  <fg=gray>Corriger la valeur, pas le plafond.</>');
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('  Aucun plafond dépassé.');
        $this->newLine();

        return self::SUCCESS;
    }
}
