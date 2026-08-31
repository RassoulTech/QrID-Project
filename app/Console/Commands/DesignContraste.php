<?php

namespace App\Console\Commands;

use App\Support\Design\Contraste;
use Illuminate\Console\Command;

/**
 * LE CONTRASTE DES TOKENS, CALCULÉ SUR LES VALEURS LIVRÉES.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'ELLE VÉRIFIE, ET CE QU'ELLE NE VÉRIFIE PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Elle lit `_tokens.scss` — la source — et mesure chaque couple
 * texte/fond possible. Elle ne rend donc jamais un chiffre périmé : si
 * quelqu'un modifie une teinte, la commande le sait au prochain passage.
 *
 * Ce qu'elle ne peut PAS faire : dire ce que voit un visiteur. Un texte
 * posé sur une photo dépend de la photo ; une couleur héritée dépend de
 * la cascade. C'est le rôle du relevé navigateur, sur le DOM rendu.
 *
 *     php artisan design:contraste
 *     php artisan design:contraste --tout    # y compris les couples conformes
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE FOND TEINTÉ — le piège qui a fait échouer treize couleurs
 * ═══════════════════════════════════════════════════════════════════════
 * Un badge pose un fond qui est SA PROPRE TEINTE à 16 %. Ce fond
 * éclaircit la surface : le texte perd du contraste contre son propre
 * fond. Mesurer la teinte contre la surface NUE donne un chiffre faux
 * dans le sens rassurant — celui qu'on ne corrige jamais.
 */
class DesignContraste extends Command
{
    protected $signature = 'design:contraste
                            {--tout : Affiche aussi les couples conformes}';

    protected $description = 'Vérifie le contraste de chaque couple de tokens, deux thèmes.';

    public function handle(): int
    {
        $tokens = $this->lireTokens();

        if ($tokens === []) {
            $this->error('  _tokens.scss illisible ou sans variable de couleur.');

            return self::FAILURE;
        }

        $themes = [
            'clair' => [
                'surfaces' => ['page' => 'c-page', 'carte' => 'c-carte', 'surélevé' => 'c-surelev'],
                'textes' => ['texte' => 'c-texte', 'texte-2' => 'c-texte-2', 'texte-3' => 'c-texte-3'],
                'etats' => ['succès' => 'c-succes', 'alerte' => 'c-alerte', 'danger' => 'c-danger',
                    'info' => 'c-info', 'neutre' => 'c-neutre'],
                'marque' => 'vert-fonce',
                'sur_marque' => '#FFFFFF',
            ],
            'sombre' => [
                'surfaces' => ['page' => 's-page', 'carte' => 's-carte', 'surélevé' => 's-surelev'],
                'textes' => ['texte' => 's-texte', 'texte-2' => 's-texte-2', 'texte-3' => 's-texte-3'],
                'etats' => ['succès' => 's-succes', 'alerte' => 's-alerte', 'danger' => 's-danger',
                    'info' => 's-info', 'neutre' => 's-neutre'],
                'marque' => 'vert-accent-sombre',
                'sur_marque' => null,          // = $s-texte-inv
            ],
        ];

        $echecs = [];
        $mesures = 0;

        foreach ($themes as $nomTheme => $t) {
            $this->newLine();
            $this->line(sprintf('  <fg=white;options=bold>THÈME %s</>', strtoupper($nomTheme)));

            $surfaces = [];
            foreach ($t['surfaces'] as $etiquette => $clef) {
                if (isset($tokens[$clef])) {
                    $surfaces[$etiquette] = $tokens[$clef];
                }
            }

            // ── Les textes, sur chaque surface ─────────────────────────
            foreach ($t['textes'] as $etiquette => $clef) {
                if (! isset($tokens[$clef])) {
                    continue;
                }
                $mesures++;
                $this->couple($etiquette, $tokens[$clef], $surfaces,
                    Contraste::SEUIL_TEXTE, $nomTheme, $echecs);
            }

            // ── Les états ──────────────────────────────────────────────
            foreach ($t['etats'] as $etiquette => $clef) {
                if (! isset($tokens[$clef])) {
                    continue;
                }

                $cibles = $surfaces;

                // Le fond doux, tel qu'il est DÉCLARÉ dans _tokens.scss :
                // opaque en thème clair, semi-transparent en sombre.
                $clefFond = ($nomTheme === 'clair' ? 'c-' : 's-')
                    .substr($clef, 2).'-fond';

                if (isset($tokens[$clefFond]) && is_string($tokens[$clefFond])) {
                    $cibles['fond doux'] = $tokens[$clefFond];
                } elseif (isset($tokens['rgba:'.$clefFond])) {
                    [$base, $alpha] = $tokens['rgba:'.$clefFond];
                    foreach ($surfaces as $s => $hex) {
                        $cibles['fond doux/'.$s] = Contraste::aplatir($base, $alpha, $hex);
                    }
                }

                $mesures++;
                $this->couple('état '.$etiquette, $tokens[$clef], $cibles,
                    Contraste::SEUIL_TEXTE, $nomTheme, $echecs);
            }

            // ── La marque, et le texte posé dessus ─────────────────────
            if (isset($tokens[$t['marque']])) {
                $marque = $tokens[$t['marque']];
                $mesures++;
                $this->couple('marque', $marque, $surfaces,
                    Contraste::SEUIL_TEXTE, $nomTheme, $echecs);

                $sur = $t['sur_marque'] ?? ($tokens['s-texte-inv'] ?? null);
                if ($sur) {
                    $mesures++;
                    $this->couple('texte sur marque', $sur, ['marque' => $marque],
                        Contraste::SEUIL_TEXTE, $nomTheme, $echecs);
                }
            }
        }

        $this->newLine();
        $this->line(sprintf('  %d couple(s) de tokens mesuré(s).', $mesures));

        if ($echecs !== []) {
            $this->newLine();
            $this->error(sprintf('  %d échec(s) :', count($echecs)));
            foreach ($echecs as $e) {
                $this->line('     '.$e);
            }
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('  Tous les couples passent leur seuil.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * @param  array<string,string>  $cibles
     * @param  list<string>  $echecs
     */
    private function couple(
        string $nom, string $teinte, array $cibles,
        float $seuil, string $theme, array &$echecs
    ): void {
        $ratios = [];
        $pire = 99.0;

        foreach ($cibles as $etiquette => $fond) {
            $r = Contraste::ratio($teinte, $fond);
            $ratios[$etiquette] = $r;
            $pire = min($pire, $r);
        }

        $ok = $pire >= $seuil;

        if (! $ok) {
            $mauvais = array_keys(array_filter($ratios, fn ($r) => $r < $seuil));
            $echecs[] = sprintf('%s / %-18s %s  min %.2f  (%s)',
                $theme, $nom, $teinte, $pire, implode(', ', $mauvais));
        }

        if ($ok && ! $this->option('tout')) {
            $this->line(sprintf('    <fg=green>✓</> %-18s %s   min %5.2f',
                $nom, $teinte, $pire));

            return;
        }

        $this->line(sprintf('    %s %-18s %s   min %5.2f   %s',
            $ok ? '<fg=green>✓</>' : '<fg=red>✗</>',
            $nom, $teinte, $pire,
            implode(' | ', array_map(
                fn ($e, $r) => sprintf('%s %.2f', $e, $r),
                array_keys($ratios), $ratios
            ))
        ));
    }

    /**
     * Lit les variables de couleur de `_tokens.scss`.
     *
     * On lit la SOURCE plutôt qu'une copie : une commande qui porterait
     * ses propres valeurs finirait par diverger du fichier qu'elle
     * prétend vérifier, et c'est exactement le défaut qu'on corrige.
     *
     * @return array<string,string>
     */
    private function lireTokens(): array
    {
        $chemin = resource_path('sass/_tokens.scss');

        if (! is_file($chemin)) {
            return [];
        }

        $contenu = (string) file_get_contents($chemin);

        // Les commentaires portent des exemples de couleurs : les lire
        // ferait mesurer des valeurs qui n'existent pas.
        $contenu = (string) preg_replace('#^\s*//.*$#m', '', $contenu);

        preg_match_all(
            '/^\s*\$([\w-]+)\s*:\s*(#[0-9A-Fa-f]{3,8})\s*;/m',
            $contenu, $m, PREG_SET_ORDER
        );

        $tokens = [];

        foreach ($m as $decl) {
            $tokens[$decl[1]] = strtoupper($decl[2]);
        }

        /*
         | LES FONDS SEMI-TRANSPARENTS, LUS TELS QU'ILS SONT DÉCLARÉS.
         |
         | Supposer que tout fond doux du thème sombre est « la teinte à
         | 16 % » était faux : `$s-neutre-fond` vaut rgba(#EAF1EE, .08),
         | une base et une opacité différentes. La commande annonçait donc
         | un échec sur un couple qui n'existe pas.
         |
         | On lit la déclaration plutôt que de la deviner.
         */
        preg_match_all(
            '/^\s*\$([\w-]+)\s*:\s*rgba\(\s*(#[0-9A-Fa-f]{3,8})\s*,\s*([\d.]+)\s*\)\s*;/m',
            $contenu, $m2, PREG_SET_ORDER
        );

        foreach ($m2 as $decl) {
            $tokens['rgba:'.$decl[1]] = [strtoupper($decl[2]), (float) $decl[3]];
        }

        return $tokens;
    }
}
