<?php

namespace App\Support;

/**
 * LES DRAPEAUX, DESSINÉS — pas des émojis.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI PAS L'ÉMOJI
 * ═══════════════════════════════════════════════════════════════════════
 * L'émoji drapeau est une paire de lettres « indicatrices régionales » que
 * la POLICE doit savoir composer. Android, iOS et macOS le font ; Windows
 * ne le fait pas, et affiche à la place les deux lettres dans un rectangle
 * gris. Le sélecteur d'indicatif montrait donc « SN » à tout client sur
 * ordinateur — exactement ce qu'un drapeau était censé éviter.
 *
 * Chaque drapeau est ici un SVG, tracé dans un cadre 3×2. Il s'affiche à
 * l'identique partout, ne dépend d'aucune police, ne coûte aucune requête.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * FIDÈLES SANS ÊTRE EXACTS
 * ═══════════════════════════════════════════════════════════════════════
 * Ces drapeaux sont lus à vingt pixels de haut, dans un coin de champ. Les
 * armoiries du Portugal, la calligraphie saoudienne ou les cinquante
 * étoiles américaines n'y seraient pas lisibles et alourdiraient chaque
 * page. Les couleurs, les partitions et les emblèmes principaux —
 * croissant, étoile, croix, disque — suffisent à reconnaître un pays.
 * C'est un repère, pas une planche d'héraldique.
 */
class Drapeaux
{
    /**
     * Le contenu d'un SVG en viewBox « 0 0 3 2 », sans la balise <svg>.
     *
     * Un pays inconnu rend un globe neutre plutôt que du vide : un carré
     * blanc au milieu d'un champ se lit comme une image qui n'a pas chargé.
     */
    public static function svg(string $code): string
    {
        return match (mb_strtoupper($code)) {
            // ─────────── Afrique de l'Ouest ───────────
            'SN' => self::bandes(false, '#00853F', '#FDEF42', '#E31B23').self::etoile(1.5, 1, .34, '#00853F'),
            'CI' => self::bandes(false, '#F77F00', '#FFFFFF', '#009E60'),
            'ML' => self::bandes(false, '#14B53A', '#FCD116', '#CE1126'),
            'GN' => self::bandes(false, '#CE1126', '#FCD116', '#009460'),
            'BF' => '<rect width="3" height="1" fill="#EF2B2D"/><rect y="1" width="3" height="1" fill="#009E49"/>'
                .self::etoile(1.5, 1, .36, '#FCD116'),
            'NE' => self::bandes(true, '#E05206', '#FFFFFF', '#0DB02B')
                .'<circle cx="1.5" cy="1" r=".3" fill="#E05206"/>',
            'TG' => '<rect width="3" height="2" fill="#FFCE00"/>'
                .'<rect width="3" height=".4" fill="#006A4E"/><rect y=".8" width="3" height=".4" fill="#006A4E"/>'
                .'<rect y="1.6" width="3" height=".4" fill="#006A4E"/>'
                .'<rect width="1.2" height="1.2" fill="#D21034"/>'.self::etoile(.6, .6, .38, '#FFFFFF'),
            'BJ' => '<rect x="1" width="2" height="1" fill="#FCD116"/>'
                .'<rect x="1" y="1" width="2" height="1" fill="#E8112D"/>'
                .'<rect width="1" height="2" fill="#008751"/>',
            'GW' => '<rect x="1" width="2" height="1" fill="#FCD116"/><rect x="1" y="1" width="2" height="1" fill="#009E49"/>'
                .'<rect width="1" height="2" fill="#CE1126"/>'.self::etoile(.5, 1, .34, '#000000'),
            'MR' => '<rect width="3" height="2" fill="#006233"/>'
                .'<rect width="3" height=".25" fill="#D01C1F"/><rect y="1.75" width="3" height=".25" fill="#D01C1F"/>'
                .self::croissant(1.5, 1.12, .48, '#FCD116', '#006233').self::etoile(1.5, .62, .16, '#FCD116'),
            'GM' => '<rect width="3" height="2" fill="#FFFFFF"/>'
                .'<rect width="3" height=".67" fill="#CE1126"/>'
                .'<rect y=".78" width="3" height=".44" fill="#0C1C8C"/>'
                .'<rect y="1.33" width="3" height=".67" fill="#3A7728"/>',
            'NG' => self::bandes(false, '#008751', '#FFFFFF', '#008751'),
            'GH' => self::bandes(true, '#CE1126', '#FCD116', '#006B3F').self::etoile(1.5, 1, .28, '#000000'),
            'CM' => self::bandes(false, '#007A5E', '#CE1126', '#FCD116').self::etoile(1.5, 1, .32, '#FCD116'),
            'GA' => self::bandes(true, '#009E60', '#FCD116', '#3A75C4'),
            'CD' => '<rect width="3" height="2" fill="#007FFF"/>'
                .'<path d="M0 1.7 2.6 0h.4v.3L.4 2H0z" fill="#F7D618"/>'
                .'<path d="M0 1.55 2.4 0h.35L.28 1.75H0z" fill="#CE1021"/>'
                .self::etoile(.42, .42, .3, '#F7D618'),
            'CG' => '<rect width="3" height="2" fill="#009543"/>'
                .'<path d="M3 0v2H0z" fill="#DC241F"/>'
                .'<path d="M2.05 0 0 1.37V.68L1.03 0z" fill="#FBDE4A"/>'
                .'<path d="M3 0v.68L.98 2H0v-.02z" fill="#FBDE4A"/>',
            'TD' => self::bandes(false, '#002664', '#FECB00', '#C60C30'),

            // ─────────── Reste du monde ───────────
            'DE' => self::bandes(true, '#000000', '#DD0000', '#FFCE00'),
            'BE' => self::bandes(false, '#000000', '#FAE042', '#ED2939'),
            'FR' => self::bandes(false, '#002395', '#FFFFFF', '#ED2939'),
            'IT' => self::bandes(false, '#008C45', '#F4F5F0', '#CD212A'),
            'ES' => '<rect width="3" height="2" fill="#AA151B"/><rect y=".5" width="3" height="1" fill="#F1BF00"/>',
            'PT' => '<rect width="3" height="2" fill="#FF0000"/><rect width="1.2" height="2" fill="#006600"/>'
                .'<circle cx="1.2" cy="1" r=".42" fill="#FFE800" stroke="#FFFFFF" stroke-width=".05"/>'
                .'<circle cx="1.2" cy="1" r=".24" fill="#FF0000"/>',
            'CH' => '<rect width="3" height="2" fill="#D52B1E"/>'
                .'<rect x="1.32" y=".4" width=".36" height="1.2" fill="#FFFFFF"/>'
                .'<rect x=".9" y=".82" width="1.2" height=".36" fill="#FFFFFF"/>',
            'GB' => '<rect width="3" height="2" fill="#012169"/>'
                .'<path d="m0 0 3 2M3 0 0 2" stroke="#FFFFFF" stroke-width=".4"/>'
                .'<path d="m0 0 3 2M3 0 0 2" stroke="#C8102E" stroke-width=".24"/>'
                .'<path d="M1.5 0v2M0 1h3" stroke="#FFFFFF" stroke-width=".66"/>'
                .'<path d="M1.5 0v2M0 1h3" stroke="#C8102E" stroke-width=".4"/>',
            'US' => self::rayures('#B31942', '#FFFFFF', 13)
                .'<rect width="1.2" height="1.077" fill="#0A3161"/>'.self::pointsEtoiles(),
            'CA' => '<rect width="3" height="2" fill="#FFFFFF"/><rect width=".75" height="2" fill="#D52B1E"/>'
                .'<rect x="2.25" width=".75" height="2" fill="#D52B1E"/>'
                .'<path d="m1.5.4.16.42.4-.1-.14.4.28.06-.36.3.08.2-.4-.07.04.44h-.12l.04-.44-.4.07.08-.2-.36-.3.28-.06-.14-.4.4.1z" fill="#D52B1E"/>',
            'CN' => '<rect width="3" height="2" fill="#DE2910"/>'.self::etoile(.55, .55, .38, '#FFDE00')
                .self::etoile(1.05, .22, .13, '#FFDE00').self::etoile(1.28, .52, .13, '#FFDE00')
                .self::etoile(1.28, .88, .13, '#FFDE00').self::etoile(1.05, 1.16, .13, '#FFDE00'),
            'MA' => '<rect width="3" height="2" fill="#C1272D"/>'
                .'<path d="'.self::traceEtoile(1.5, 1, .46).'" fill="none" stroke="#006233" stroke-width=".09"/>',
            'TN' => '<rect width="3" height="2" fill="#E70013"/><circle cx="1.5" cy="1" r=".62" fill="#FFFFFF"/>'
                .self::croissant(1.55, 1, .42, '#E70013', '#FFFFFF').self::etoile(1.62, 1, .2, '#E70013'),
            'TR' => '<rect width="3" height="2" fill="#E30A17"/>'
                .self::croissant(1.1, 1, .44, '#FFFFFF', '#E30A17').self::etoile(1.62, 1, .22, '#FFFFFF'),
            'SA' => '<rect width="3" height="2" fill="#165D31"/>'
                .'<rect x=".5" y=".62" width="2" height=".26" rx=".13" fill="#FFFFFF"/>'
                .'<rect x=".5" y="1.2" width="2" height=".12" rx=".06" fill="#FFFFFF"/>'
                .'<path d="M2.5 1.26v-.22l.22.16z" fill="#FFFFFF"/>',
            'AE' => '<rect x=".75" width="2.25" height=".67" fill="#00732F"/>'
                .'<rect x=".75" y=".67" width="2.25" height=".66" fill="#FFFFFF"/>'
                .'<rect x=".75" y="1.33" width="2.25" height=".67" fill="#000000"/>'
                .'<rect width=".75" height="2" fill="#FF0000"/>',

            default => '<rect width="3" height="2" fill="#E8ECEA"/>'
                .'<circle cx="1.5" cy="1" r=".62" fill="none" stroke="#8B9691" stroke-width=".12"/>'
                .'<path d="M.88 1h1.24M1.5.38a1.6 1.6 0 0 1 0 1.24 1.6 1.6 0 0 1 0-1.24" fill="none" stroke="#8B9691" stroke-width=".1"/>',
        };
    }

    /**
     * TOUS LES DRAPEAUX, EN UN SEUL BLOC RÉUTILISABLE.
     *
     * Le sélecteur d'indicatif doit pouvoir changer de drapeau sans aller
     * rien redemander au serveur : les trente-cinq tracés sont donc posés
     * une fois dans la page, en <symbol>, et le drapeau visible n'est qu'un
     * <use> dont le script change la cible.
     *
     * Une seule fois par page, quel que soit le nombre de champs téléphone :
     * c'est le rôle du @once qui l'entoure dans x-drapeau.
     */
    public static function sprite(): string
    {
        $svg = '';

        foreach (array_keys(IndicatifsPays::catalogue()) as $code) {
            $svg .= sprintf(
                '<symbol id="drapeau-%s" viewBox="0 0 3 2">%s</symbol>',
                $code,
                self::svg($code)
            );
        }

        return $svg.'<symbol id="drapeau-inconnu" viewBox="0 0 3 2">'.self::svg('??').'</symbol>';
    }

    /** Trois bandes égales, horizontales ($horizontal) ou verticales. */
    private static function bandes(bool $horizontal, string ...$couleurs): string
    {
        $svg = '';
        $pas = ($horizontal ? 2 : 3) / count($couleurs);

        foreach (array_values($couleurs) as $i => $couleur) {
            $svg .= $horizontal
                ? sprintf('<rect y="%.4f" width="3" height="%.4f" fill="%s"/>', $i * $pas, $pas, $couleur)
                : sprintf('<rect x="%.4f" width="%.4f" height="2" fill="%s"/>', $i * $pas, $pas, $couleur);
        }

        return $svg;
    }

    /** N rayures horizontales alternées — les treize américaines. */
    private static function rayures(string $a, string $b, int $nombre): string
    {
        $svg = sprintf('<rect width="3" height="2" fill="%s"/>', $b);
        $hauteur = 2 / $nombre;

        for ($i = 0; $i < $nombre; $i += 2) {
            $svg .= sprintf('<rect y="%.4f" width="3" height="%.4f" fill="%s"/>', $i * $hauteur, $hauteur, $a);
        }

        return $svg;
    }

    /** Une grille de points blancs : les étoiles, à cette taille. */
    private static function pointsEtoiles(): string
    {
        $svg = '';

        for ($ligne = 0; $ligne < 4; $ligne++) {
            for ($colonne = 0; $colonne < 5; $colonne++) {
                $svg .= sprintf(
                    '<circle cx="%.3f" cy="%.3f" r=".05" fill="#FFFFFF"/>',
                    .16 + $colonne * .22,
                    .16 + $ligne * .25
                );
            }
        }

        return $svg;
    }

    /**
     * UN CROISSANT, PAR SOUSTRACTION.
     *
     * Un disque plein, puis un second disque décalé peint dans la couleur du
     * fond. Plus court et plus sûr qu'un arc calculé, qui se déforme dès que
     * le rayon change.
     */
    private static function croissant(float $cx, float $cy, float $r, string $couleur, string $fond): string
    {
        return sprintf(
            '<circle cx="%.3f" cy="%.3f" r="%.3f" fill="%s"/><circle cx="%.3f" cy="%.3f" r="%.3f" fill="%s"/>',
            $cx, $cy, $r, $couleur,
            $cx + $r * .30, $cy, $r * .82, $fond
        );
    }

    private static function etoile(float $cx, float $cy, float $r, string $couleur): string
    {
        return sprintf('<path d="%s" fill="%s"/>', self::traceEtoile($cx, $cy, $r), $couleur);
    }

    /** Une étoile à cinq branches, pointe en haut. */
    private static function traceEtoile(float $cx, float $cy, float $r): string
    {
        $points = [];

        for ($i = 0; $i < 10; $i++) {
            $rayon = $i % 2 === 0 ? $r : $r * .382;
            $angle = -M_PI / 2 + $i * M_PI / 5;

            $points[] = sprintf('%.3f %.3f', $cx + $rayon * cos($angle), $cy + $rayon * sin($angle));
        }

        return 'M'.implode('L', $points).'Z';
    }
}
