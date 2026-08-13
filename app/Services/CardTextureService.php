<?php

namespace App\Services;

use App\Enums\VarianteCarte;
use Illuminate\Support\Facades\Storage;

/**
 * LE FOND ORGANIQUE DU VERSO, POUR L'IMPRESSION.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE IMAGE, ALORS QUE L'ÉCRAN S'EN PASSE
 * ═══════════════════════════════════════════════════════════════════════
 * À l'écran, ces formes sont six dégradés radiaux en CSS — gratuits, nets à
 * toute taille. DomPDF n'en rend aucun : il ne connaît que le dégradé
 * LINÉAIRE. Le verso imprimé se retrouvait donc avec un simple aplat, quand
 * l'écran montrait autre chose.
 *
 * On produit donc ici la même intention en pixels. C'est le seul endroit du
 * projet où une image remplace du CSS, et c'est pour une raison précise : la
 * carte part chez un imprimeur, où la fidélité prime sur l'élégance du moyen.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA TECHNIQUE — dessiner petit, puis agrandir
 * ═══════════════════════════════════════════════════════════════════════
 * Les formes sont peintes sur une image MINUSCULE (160 × 101 px) par empilement
 * d'ellipses concentriques, puis l'image est agrandie par rééchantillonnage
 * bicubique jusqu'à la taille d'impression.
 *
 * C'est ce rééchantillonnage qui fait tout le travail : il transforme des
 * paliers d'ellipses en un dégradé continu, sans une seule passe de flou. La
 * démarche inverse — peindre grand puis flouter — coûterait cent fois plus et
 * rendrait moins bien, le flou gaussien de GD étant à la fois faible et cher.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * PAS DE GÉOMÉTRIE RÉPÉTITIVE, PAR CONSTRUCTION
 * ═══════════════════════════════════════════════════════════════════════
 * Les six formes ont des tailles, des positions et des opacités toutes
 * différentes, écrites en dur. Aucune boucle ne les dispose sur une grille :
 * il n'y a donc aucun pas régulier à percevoir. C'est précisément ce qui
 * manquait à la trame diagonale, dont l'œil lisait immédiatement la cadence.
 */
class CardTextureService
{
    /** Résolution de travail. Petite : c'est l'agrandissement qui lisse. */
    private const TRAVAIL_L = 160;

    private const TRAVAIL_H = 101;   // 160 / 1,586

    /** Résolution finale. 1832 px sur 91,6 mm ≈ 508 dpi. */
    private const RENDU_L = 1832;

    private const RENDU_H = 1200;

    /**
     * Les six formes : [x, y, rayon x, rayon y, teinte, opacité].
     *
     * Coordonnées et rayons en FRACTION de la largeur ou de la hauteur, pour
     * rester valables à toute résolution. « clair » prend la couleur de
     * l'encre, « sombre » un noir — ce qui donne des voiles lumineux sur la
     * carte verte et des ombres vertes sur la blanche, sans seconde table.
     */
    private const FORMES = [
        [0.18, 0.76, 0.38, 0.52, 'clair', 0.09],
        [0.62, 0.18, 0.30, 0.38, 'clair', 0.07],
        [0.88, 0.62, 0.46, 0.58, 'clair', 0.06],
        [0.40, 0.92, 0.26, 0.34, 'clair', 0.05],
        [0.06, 0.12, 0.54, 0.42, 'sombre', 0.14],
        [0.96, 0.04, 0.34, 0.46, 'sombre', 0.10],
    ];

    /** Le fond de la variante, en PNG. Écrit une fois, relu ensuite. */
    public function png(VarianteCarte $variante): string
    {
        $chemin = "cartes/fond-{$variante->name}.png";
        $disque = Storage::disk('public');

        if (! $disque->exists($chemin)) {
            $disque->put($chemin, $this->peindre($variante));
        }

        return (string) $disque->get($chemin);
    }

    /** Prêt à poser dans un src="" de DomPDF. */
    public function dataUri(VarianteCarte $variante): string
    {
        return 'data:image/png;base64,'.base64_encode($this->png($variante));
    }

    /** Efface les fonds : à appeler si la palette change. */
    public function forget(): void
    {
        foreach (VarianteCarte::cases() as $variante) {
            Storage::disk('public')->delete("cartes/fond-{$variante->name}.png");
        }
    }

    // -----------------------------------------------------------------------

    private function peindre(VarianteCarte $variante): string
    {
        $petite = imagecreatetruecolor(self::TRAVAIL_L, self::TRAVAIL_H);

        [$fr, $fv, $fb] = $this->rgb($variante->fond());
        imagefill($petite, 0, 0, imagecolorallocate($petite, $fr, $fv, $fb));

        // L'alpha blending doit être actif : sans lui, chaque ellipse écraserait
        // la précédente au lieu de s'y superposer, et il ne resterait que la
        // dernière forme dessinée.
        imagealphablending($petite, true);

        foreach (self::FORMES as [$x, $y, $rx, $ry, $teinte, $opacite]) {
            $this->forme(
                $petite,
                (int) round($x * self::TRAVAIL_L),
                (int) round($y * self::TRAVAIL_H),
                (int) round($rx * self::TRAVAIL_L),
                (int) round($ry * self::TRAVAIL_H),
                $teinte === 'clair' ? $this->rgb($variante->encre()) : [0, 0, 0],
                $opacite
            );
        }

        // L'AGRANDISSEMENT EST L'ÉTAPE QUI LISSE. imagecopyresampled interpole
        // entre les pixels : les paliers d'ellipses deviennent un dégradé.
        $grande = imagecreatetruecolor(self::RENDU_L, self::RENDU_H);

        imagecopyresampled(
            $grande, $petite,
            0, 0, 0, 0,
            self::RENDU_L, self::RENDU_H,
            self::TRAVAIL_L, self::TRAVAIL_H
        );

        ob_start();
        imagepng($grande, null, 9);
        $binaire = (string) ob_get_clean();

        imagedestroy($petite);
        imagedestroy($grande);

        return $binaire;
    }

    /**
     * Une forme diffuse : des ellipses concentriques d'opacité décroissante.
     *
     * L'opacité suit le CARRÉ de la distance au centre, non la distance elle-
     * même. Une décroissance linéaire laisse un bord perceptible — un anneau
     * fantôme là où la dernière ellipse s'arrête. Au carré, la chute est douce
     * au centre et brutale au bord, ce qui est exactement le profil d'un halo.
     *
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    private function forme(\GdImage $image, int $cx, int $cy, int $rx, int $ry, array $rgb, float $opacite): void
    {
        $paliers = 28;

        for ($i = $paliers; $i > 0; $i--) {
            $part = $i / $paliers;

            // 127 = totalement transparent pour GD, 0 = opaque. L'échelle est
            // inversée par rapport à toutes les autres du projet.
            $alpha = (int) round(127 - 127 * $opacite * (1 - $part) ** 2);

            $couleur = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], min(127, max(0, $alpha)));

            imagefilledellipse(
                $image,
                $cx, $cy,
                (int) round($rx * 2 * $part),
                (int) round($ry * 2 * $part),
                $couleur
            );
        }
    }

    /** « #0B3B2E » → [11, 59, 46]. */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            $hex = '0B3B2E';
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
