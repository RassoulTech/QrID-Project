<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * LES DEUX LANGUES DOIVENT PORTER EXACTEMENT LES MÊMES CLÉS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE COMMANDE, ET PAS UNE RELECTURE
 * ═══════════════════════════════════════════════════════════════════════
 * Une clé présente en français et absente en anglais ne provoque AUCUNE
 * erreur. Laravel rend alors la clé elle-même : la page affiche
 * « admin.clients.titre » à l'endroit d'un titre. Rien dans les journaux,
 * rien dans les tests, rien à l'écran côté français — le défaut ne se voit
 * que dans l'autre langue, celle qu'on regarde le moins.
 *
 * C'est la panne qui revient toujours : on ajoute une phrase, on la traduit
 * « plus tard », et « plus tard » arrive le jour où un anglophone ouvre la
 * page.
 *
 *     php artisan lang:check
 *
 * Le code de sortie est non nul dès qu'un écart existe : l'intégration
 * continue peut s'y brancher sans lire la sortie.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'ELLE VÉRIFIE EN PLUS DE LA SYMÉTRIE
 * ═══════════════════════════════════════════════════════════════════════
 * Une clé peut exister des deux côtés et rester VIDE, ou n'être qu'une
 * copie mot pour mot du français. Le premier cas affiche du blanc, le
 * second passe la symétrie sans que rien ne soit traduit. Les deux sont
 * signalés à part, car ils ne sont pas des erreurs de la même nature :
 * une chaîne vide est toujours un défaut ; une chaîne identique peut être
 * légitime (« Instagram », « Orange Money », « QrID »).
 */
class VerifierTraductions extends Command
{
    protected $signature = 'lang:check
                            {--strict : Traite aussi les valeurs identiques comme des écarts}';

    protected $description = 'Compare lang/fr et lang/en et signale toute clé manquante.';

    /** Valeurs légitimement identiques dans les deux langues. */
    private const IDENTIQUES_ADMISES = [
        'QrID', 'Instagram', 'LinkedIn', 'Facebook', 'WhatsApp', 'TikTok',
        'YouTube', 'Twitter', 'Snapchat', 'Telegram', 'Wave', 'Orange Money',
        'FCFA', 'PDF', 'QR Code', 'NFC', 'Email', 'Google', 'SMS', 'URL',
    ];

    public function handle(): int
    {
        $base = lang_path();

        $fr = $this->aplatir($this->charger($base.'/fr'));
        $en = $this->aplatir($this->charger($base.'/en'));

        $manqueEn = array_diff_key($fr, $en);
        $manqueFr = array_diff_key($en, $fr);

        $vides = [];
        $identiques = [];

        foreach (array_intersect_key($fr, $en) as $cle => $valeurFr) {
            /*
             | LA COMPARAISON PORTE SUR LA CHAINE NUE, PAS SUR SON trim().
             |
             | `common.formats.separateur_milliers` vaut une espace en
             | francais : c'est une valeur DELIBEREE, et la seule correcte.
             | Un trim() la reduisait a la chaine vide, et le controle
             | signalait comme defaut le fonctionnement normal.
             |
             | Une chaine reellement vide reste un defaut ; une chaine qui
             | ne contient qu'une espace n'en est pas un.
             */
            if ((string) $valeurFr === '' || (string) $en[$cle] === '') {
                $vides[] = $cle;

                continue;
            }

            if ($valeurFr === $en[$cle] && ! $this->identiqueAdmise($valeurFr)) {
                $identiques[$cle] = $valeurFr;
            }
        }

        $this->newLine();
        $this->line('  <fg=white;options=bold>SYMÉTRIE DES TRADUCTIONS</>');
        $this->line(sprintf('  fr : %d clés    en : %d clés', count($fr), count($en)));
        $this->newLine();

        $ecarts = 0;

        $ecarts += $this->rapporter('Absentes de lang/en', array_keys($manqueEn), $fr);
        $ecarts += $this->rapporter('Absentes de lang/fr', array_keys($manqueFr), $en);
        $ecarts += $this->rapporter('Valeur vide', $vides, $fr);

        if ($identiques !== []) {
            $strict = (bool) $this->option('strict');

            $this->line(sprintf(
                '  <fg=%s>%s</> %d clé(s) identiques dans les deux langues',
                $strict ? 'red' : 'yellow',
                $strict ? '✗' : '!',
                count($identiques)
            ));

            foreach (array_slice($identiques, 0, 15, true) as $cle => $valeur) {
                $this->line(sprintf('      %-46s « %s »', $cle, mb_strimwidth($valeur, 0, 40, '…')));
            }

            if (count($identiques) > 15) {
                $this->line(sprintf('      … et %d autres', count($identiques) - 15));
            }

            $this->newLine();

            if ($strict) {
                $ecarts += count($identiques);
            }
        }

        if ($ecarts > 0) {
            $this->error(sprintf('  %d écart(s).', $ecarts));

            return self::FAILURE;
        }

        $this->info('  Les deux langues portent exactement les mêmes clés.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $cles
     * @param  array<string, string>  $source
     */
    private function rapporter(string $titre, array $cles, array $source): int
    {
        if ($cles === []) {
            return 0;
        }

        $this->line(sprintf('  <fg=red>✗</> %s — %d clé(s)', $titre, count($cles)));

        foreach (array_slice($cles, 0, 25) as $cle) {
            $this->line(sprintf('      %-46s « %s »', $cle,
                mb_strimwidth((string) ($source[$cle] ?? ''), 0, 40, '…')));
        }

        if (count($cles) > 25) {
            $this->line(sprintf('      … et %d autres', count($cles) - 25));
        }

        $this->newLine();

        return count($cles);
    }

    private function identiqueAdmise(string $valeur): bool
    {
        // Une valeur courte sans espace est le plus souvent un nom propre ou
        // une unité — « FCFA », « PDF ». Les traduire n'aurait pas de sens.
        if (in_array(trim($valeur), self::IDENTIQUES_ADMISES, true)) {
            return true;
        }

        // Un nombre, un symbole, une chaîne de format pure.
        return (bool) preg_match('/^[\s\d\p{P}\p{S}:]*$/u', $valeur);
    }

    /** @return array<string, mixed> */
    private function charger(string $dossier): array
    {
        if (! is_dir($dossier)) {
            return [];
        }

        $tout = [];

        foreach (glob($dossier.'/*.php') ?: [] as $fichier) {
            $tout[basename($fichier, '.php')] = require $fichier;
        }

        return $tout;
    }

    /**
     * Aplatit « auth » => [« login » => [« titre » => X]] en « auth.login.titre ».
     *
     * @param  array<string, mixed>  $tableau
     * @return array<string, string>
     */
    private function aplatir(array $tableau, string $prefixe = ''): array
    {
        $plat = [];

        foreach ($tableau as $cle => $valeur) {
            $chemin = $prefixe === '' ? (string) $cle : $prefixe.'.'.$cle;

            if (is_array($valeur)) {
                $plat += $this->aplatir($valeur, $chemin);

                continue;
            }

            $plat[$chemin] = (string) $valeur;
        }

        return $plat;
    }
}
