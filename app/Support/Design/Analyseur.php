<?php

namespace App\Support\Design;

/**
 * LE MOTEUR D'ANALYSE — partagé par design:check et design:audit.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * TROIS VERSIONS FAUSSES AVANT CELLE-CI
 * ═══════════════════════════════════════════════════════════════════════
 * Le relevé manuel a rendu successivement 1963, puis 651, puis 1598
 * occurrences. L'écart n'était pas dans le code audité : il était dans
 * l'auditeur.
 *
 *   · 1963 — il comptait les commentaires explicatifs, les tracés SVG et
 *     les noms de classes CSS ;
 *   · 651  — en écartant tout ce qui contenait `="`, il jetait avec le
 *     bruit les attributs de composants Blade, qui sont de VRAIES
 *     occurrences (`libelle="Désactiver le profil"`) ;
 *   · 1598 — en séparant enfin le code du texte affiché.
 *
 * Un audit qui gonfle son chiffre ne sert à rien ; un audit qui le rabote
 * en jetant du vrai est pire, parce qu'il donne le sentiment d'avoir fini.
 *
 * La règle qui en sort : ON NE DEVINE PAS. Chaque exclusion est nommée,
 * justifiée, et testée.
 */
class Analyseur
{
    /**
     * Les deux fichiers autorisés à porter des valeurs.
     *
     * `_tokens.scss` est la source de vérité. `_variables.scss` subsiste
     * tant que la surcharge Bootstrap l'exige : Bootstrap lit des
     * variables SCSS et ne sait pas résoudre une propriété CSS.
     */
    public const SOURCES = ['_tokens.scss', '_variables.scss'];

    /**
     * LONGUEURS TOLÉRÉES, ET POURQUOI CHACUNE.
     *
     * Une liste blanche sans justification devient un tiroir où l'on
     * range ce qu'on ne veut pas corriger. Chaque entrée porte donc sa
     * raison, et il n'y en a que six.
     */
    public const LONGUEURS_TOLEREES = [
        '0'     => 'la valeur nulle n\'a pas d\'unité et ne dérive pas',
        '1px'   => 'épaisseur d\'une bordure : une échelle n\'a pas de sens sous 2px',
        '2px'   => 'épaisseur d\'un contour de focus, imposée par la loi 8',
        '100%'  => 'proportion, pas une longueur',
        '50%'   => 'proportion, pas une longueur',
        '1em'   => 'relatif à la police courante, donc déjà lié à l\'échelle',
        '.9em'  => 'idem — taille du code en ligne',
        '0.9em' => 'idem, écriture longue',
    ];

    /** Les vues où le style en ligne est IMPOSÉ par la cible, pas choisi. */
    public const VUES_EXEMPTES = [
        'resources/views/emails/',
        'resources/views/profile/printable.blade.php',
    ];

    /** @var array<string, list<array{ligne:int, valeur:string}>> */
    private array $constats = [];

    private int $total = 0;

    /**
     * @return array{constats: array<string, mixed>, total: int}
     */
    public function analyser(string $racine): array
    {
        $this->constats = [];
        $this->total = 0;

        foreach ($this->fichiers($racine.'/resources/sass', 'scss') as $chemin) {
            $this->scannerScss($chemin, $racine);
        }

        foreach ($this->fichiers($racine.'/resources/views', 'php') as $chemin) {
            $this->scannerVue($chemin, $racine);
        }

        return ['constats' => $this->constats, 'total' => $this->total];
    }

    // =====================================================================
    // SCSS
    // =====================================================================

    private function scannerScss(string $chemin, string $racine): void
    {
        $nom = basename($chemin);
        $relatif = $this->relatif($chemin, $racine);
        $source = in_array($nom, self::SOURCES, true);

        $lignes = explode("\n", $this->sansCommentairesScss(
            (string) file_get_contents($chemin)
        ));

        foreach ($lignes as $i => $ligne) {
            $num = $i + 1;

            // `!important` — compté PARTOUT, y compris dans les sources :
            // c'est un plafond, pas une valeur.
            foreach ($this->trouver('/!important/', $ligne) as $v) {
                $this->noter('important', $relatif, $num, $v);
            }

            // `@media (max-width: …)` — la loi 3 les interdit.
            if (preg_match('/@media[^{]*max-width/i', $ligne)) {
                $this->noter('max-width', $relatif, $num, trim($ligne));
            }

            // Le soulignement — la loi 8.
            if (preg_match('/text-decoration\s*:\s*[^;{}]*underline/i', $ligne)) {
                $this->noter('souligne', $relatif, $num, trim($ligne));
            }

            if ($source) {
                continue;   // une source a le droit de porter des valeurs
            }

            foreach ($this->couleurs($ligne) as $v) {
                $this->noter('couleur', $relatif, $num, $v);
            }
            foreach ($this->longueurs($ligne) as $v) {
                $this->noter('longueur', $relatif, $num, $v);
            }
            foreach ($this->durees($ligne) as $v) {
                $this->noter('duree', $relatif, $num, $v);
            }
        }
    }

    /**
     * Les couleurs littérales.
     *
     * `rgba($vert, .1)` est LÉGITIME : la teinte vient d'une variable,
     * seule l'opacité est locale. On ne retient donc que les formes dont
     * les trois canaux sont des nombres.
     *
     * @return list<string>
     */
    private function couleurs(string $ligne): array
    {
        $trouves = [];

        // Un hex, sauf s'il est dans une URL encodée (%23 dans un data:).
        if (! str_contains($ligne, 'data:image')) {
            $trouves = array_merge($trouves,
                $this->trouver('/#[0-9A-Fa-f]{3,8}\b/', $ligne));
        }

        $trouves = array_merge($trouves,
            $this->trouver('/\brgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+/i', $ligne),
            $this->trouver('/\bhsla?\(\s*\d/i', $ligne));

        return $trouves;
    }

    /**
     * Les longueurs littérales dans les propriétés qui touchent au
     * rythme et à l'échelle.
     *
     * On ne balaie PAS toute longueur du fichier : une largeur de trait
     * SVG ou un `translate(-50%)` ne relèvent pas d'une échelle
     * d'espacement, et les compter noierait le signal.
     *
     * @return list<string>
     */
    private function longueurs(string $ligne): array
    {
        $motif = '/\b(font-size|margin|padding|gap|border-radius|'
               .'row-gap|column-gap|inset|top|right|bottom|left)'
               .'(?:-(?:top|right|bottom|left|inline|block|start|end|x|y))?'
               .'\s*:\s*([^;{}]+)/i';

        if (! preg_match_all($motif, $ligne, $m, PREG_SET_ORDER)) {
            return [];
        }

        $trouves = [];

        foreach ($m as $decl) {
            $valeur = $decl[2];

            // Une déclaration qui référence quoi que ce soit — variable
            // SCSS, interpolation, propriété CSS, fonction d'échelle —
            // est conforme par construction.
            if (preg_match('/\$|#\{|var\(--|esp\(|typo\(|rayon\(/', $valeur)) {
                continue;
            }

            foreach ($this->trouver('/-?\d*\.?\d+(?:px|rem|em)\b/', $valeur) as $v) {
                if ($this->longueurToleree($v)) {
                    continue;
                }
                $trouves[] = trim($decl[1]).': '.$v;
            }
        }

        return $trouves;
    }

    /** @return list<string> */
    private function durees(string $ligne): array
    {
        if (! preg_match_all(
            '/\b(?:transition|animation)(?:-duration|-delay)?\s*:\s*([^;{}]+)/i',
            $ligne, $m, PREG_SET_ORDER)) {
            return [];
        }

        $trouves = [];

        foreach ($m as $decl) {
            if (preg_match('/\$|#\{|var\(--/', $decl[1])) {
                continue;
            }
            // `.01ms` est la valeur de neutralisation de
            // prefers-reduced-motion : elle n'a pas à suivre l'échelle,
            // son rôle est justement de ne durer aucun temps perceptible.
            foreach ($this->trouver('/\d*\.?\d+m?s\b/', $decl[1]) as $v) {
                if (in_array($v, ['.01ms', '0.01ms', '0s', '0ms'], true)) {
                    continue;
                }
                $trouves[] = $v;
            }
        }

        return $trouves;
    }

    private function longueurToleree(string $valeur): bool
    {
        if (isset(self::LONGUEURS_TOLEREES[$valeur])) {
            return true;
        }

        return (float) $valeur === 0.0;
    }

    // =====================================================================
    // VUES BLADE
    // =====================================================================

    private function scannerVue(string $chemin, string $racine): void
    {
        $relatif = $this->relatif($chemin, $racine);

        if (! str_ends_with($relatif, '.blade.php')) {
            return;
        }

        // Les e-mails et le PDF portent un style en ligne IMPOSÉ par leur
        // cible : aucun client de messagerie ne lit une feuille externe,
        // et le moteur PDF n'y accède pas non plus. Les exempter n'est
        // pas une facilité, c'est la reconnaissance d'une contrainte.
        foreach (self::VUES_EXEMPTES as $exempt) {
            if (str_starts_with($relatif, $exempt)) {
                return;
            }
        }

        $brut = (string) file_get_contents($chemin);

        // Les commentaires Blade sont des notes, pas de l'interface.
        $src = (string) preg_replace_callback(
            '/\{\{--.*?--\}\}/s',
            fn ($m) => str_repeat("\n", substr_count($m[0], "\n")),
            $brut
        );

        foreach (explode("\n", $src) as $i => $ligne) {
            $num = $i + 1;

            foreach ($this->trouver('/\sstyle\s*=\s*"[^"]+"/', $ligne) as $v) {
                $this->noter('style-en-ligne', $relatif, $num, trim($v));
            }

            if (! str_contains($ligne, 'data:image')) {
                foreach ($this->trouver('/#[0-9A-Fa-f]{6}\b/', $ligne) as $v) {
                    $this->noter('couleur', $relatif, $num, $v);
                }
            }

            // Un lien mort : il a l'air cliquable et ne mène nulle part.
            foreach ($this->trouver('/href\s*=\s*"#"/', $ligne) as $v) {
                $this->noter('lien-mort', $relatif, $num, $v);
            }
        }
    }

    // =====================================================================
    // OUTILS
    // =====================================================================

    private function sansCommentairesScss(string $t): string
    {
        $t = (string) preg_replace_callback(
            '#/\*.*?\*/#s',
            fn ($m) => str_repeat("\n", substr_count($m[0], "\n")),
            $t
        );

        return (string) preg_replace('#^\s*//.*$#m', '', $t);
    }

    /** @return list<string> */
    private function trouver(string $motif, string $sujet): array
    {
        return preg_match_all($motif, $sujet, $m) ? $m[0] : [];
    }

    private function noter(string $categorie, string $fichier, int $ligne, string $valeur): void
    {
        $this->constats[$categorie][] = [
            'fichier' => $fichier,
            'ligne' => $ligne,
            'valeur' => mb_strimwidth($valeur, 0, 70, '…'),
        ];
        $this->total++;
    }

    /**
     * Un chemin relatif, TOUJOURS en barres obliques.
     *
     * Sous Windows, RecursiveDirectoryIterator rend des chemins mêlant
     * les deux séparateurs. Comparer une exemption écrite en « / » à un
     * chemin qui contient des « \ » ne mord jamais : les 14 gabarits
     * d'e-mail étaient comptés alors qu'ils sont explicitement exemptés,
     * et le total des styles en ligne annonçait 191 au lieu de 63.
     *
     * On normalise DES DEUX CÔTÉS avant de retrancher la racine.
     */
    private function relatif(string $chemin, string $racine): string
    {
        $chemin = str_replace('\\', '/', $chemin);
        $racine = rtrim(str_replace('\\', '/', $racine), '/').'/';

        return str_starts_with($chemin, $racine)
            ? substr($chemin, strlen($racine))
            : $chemin;
    }


    /** @return list<string> */
    private function fichiers(string $dossier, string $extension): array
    {
        if (! is_dir($dossier)) {
            return [];
        }

        $sortie = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dossier, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === $extension) {
                $sortie[] = $f->getPathname();
            }
        }

        sort($sortie);

        return $sortie;
    }
}
