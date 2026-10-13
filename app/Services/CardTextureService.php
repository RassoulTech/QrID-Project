<?php

namespace App\Services;

use App\Enums\VarianteCarte;
use Illuminate\Support\Facades\Storage;

/**
 * LE FOND DE LA CARTE IMPRIMÉE — claustra et lisière, en pixels.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE IMAGE, ALORS QUE L'ÉCRAN S'EN PASSE
 * ═══════════════════════════════════════════════════════════════════════
 * À l'écran, la direction A « Teranga institutionnel » signe ses surfaces
 * vertes de deux motifs SVG : le CLAUSTRA en filigrane (le bloc ajouré des
 * façades modernistes de Dakar, des cercles qui se touchent) et la LISIÈRE
 * tissée (losanges latérite et or sur baobab) qui ferme le bas de la carte.
 *
 * DomPDF ne sait répéter ni un motif SVG ni un dégradé radial. On produit donc
 * ici la même intention en pixels : c'est le seul endroit du projet où une
 * image remplace du CSS, pour une raison précise — la carte part chez un
 * imprimeur, où la fidélité prime sur l'élégance du moyen.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX FONDS, UN PAR VARIANTE — et le verso emprunte celui de la verte
 * ═══════════════════════════════════════════════════════════════════════
 *   Verte   : baobab plein, claustra ivoire à 14 % sur toute la surface,
 *             lisière en bas. Recto de la carte verte ET verso de TOUTES les
 *             cartes : le verso est la face de la plateforme, identique pour
 *             tous les clients, toujours en baobab.
 *   Blanche : ivoire, claustra baobab à 13 % dans le coin inférieur gauche
 *             seulement, fondu en diagonale, lisière en bas. Recto de la
 *             carte blanche.
 *
 * AUCUN DÉGRADÉ DE COULEUR DE FOND, par construction : l'imprimeur reçoit
 * des aplats. Les anciens halos (ellipses fondues) sont abandonnés — sur la
 * carte blanche, ils s'imprimaient en taches grises dans les angles.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA TECHNIQUE — une tuile calculée, puis recopiée
 * ═══════════════════════════════════════════════════════════════════════
 * Le claustra est périodique : on calcule UNE tuile, pixel par pixel, avec
 * une couverture analytique (distance au cercle comparée à la demi-épaisseur
 * du trait) — d'où des bords lissés sans suréchantillonnage ni flou. La tuile
 * est ensuite recopiée sur la page avec imagecopy, au pixel entier : aucune
 * interpolation, aucune dérive de pas.
 *
 * Le motif est CALÉ SUR LE TRAIT DE COUPE, pas sur le bord de page : les
 * centres des cercles tombent aux coins de la carte coupée, exactement comme
 * le motif CSS part du coin de la carte à l'écran.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES CONTRAINTES D'IMPRESSION, RESPECTÉES ICI
 * ═══════════════════════════════════════════════════════════════════════
 * Le fond couvre TOUTE la page, 3 mm de fonds perdus compris : une dérive de
 * massicot ne laisse jamais de liseré. La lisière, elle, est posée sur les
 * 1,4 mm VISIBLES au-dessus du trait de coupe bas, et son fond baobab court
 * jusqu'au bord de page : si la coupe descend, la bande s'épaissit au lieu de
 * laisser apparaître l'ivoire.
 */
class CardTextureService
{
    /**
     * Résolution finale : 20 px par millimètre, soit ≈ 508 dpi.
     *
     * 1832 × 1200 px pour une page de 91,6 × 60 mm (carte + fonds perdus).
     * Un trait de claustra mesure 0,19 mm : à 300 dpi il ne ferait que deux
     * pixels et s'imprimerait en escalier.
     */
    private const PX_MM = 20;

    private const RENDU_L = 1832;

    private const RENDU_H = 1200;

    /** Fonds perdus, en millimètres : le motif se cale sur le trait de coupe. */
    private const FONDS_PERDUS = 3.0;

    /** Bas de la carte coupée, depuis le haut de page : 3 + 54 mm. */
    private const BAS_CARTE = 57.0;

    /**
     * Le motif dessiné. Change-le et les fonds déjà écrits sur le disque
     * deviennent invisibles : le nom de fichier le porte, ils seront refaits
     * au premier appel — aucune purge à penser au déploiement.
     */
    private const MOTIF = 'teranga-1';

    /*
     | LA PALETTE — celle de la direction A, en dur ici comme dans la vue PDF :
     | ni l'une ni l'autre ne lit les jetons SCSS, qui n'existent qu'à l'écran.
     */
    private const BAOBAB = '#0D3B2E';

    private const IVOIRE = '#FFFCF6';

    private const LATERITE = '#C2613A';

    private const OR = '#E2B85A';

    private const SABLE = '#F5EFE4';

    /**
     * CLAUSTRA — tuile de 40 unités : cercles de rayon 20 centrés aux coins,
     * trait de 1,2, point de 1,8 au centre. Mêmes proportions que le SVG de
     * la maquette. Taille de tuile : 1,9 em de carte sur le vert (6,25 mm),
     * 1,6 em dans le coin ivoire (5,25 mm) — entières en pixels.
     */
    private const TUILE_VERT = 125;

    private const TUILE_COIN = 105;

    private const TRAIT = 1.2 / 40;

    private const POINT = 1.8 / 40;

    /** Hauteur visible de la lisière : 0,42 em de carte. */
    private const LISIERE_MM = 1.4;

    /** Le fond de la variante, en PNG. Écrit une fois, relu ensuite. */
    public function png(VarianteCarte $variante): string
    {
        $chemin = $this->chemin($variante);
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

    /**
     * Efface les fonds : à appeler si la palette change.
     *
     * Les fichiers de l'ancien motif (« fond-Verte.png », sans version) sont
     * effacés avec les autres : ils ne sont plus lus, rien ne justifie qu'ils
     * restent sur le disque.
     */
    public function forget(): void
    {
        foreach (VarianteCarte::cases() as $variante) {
            Storage::disk('public')->delete([
                $this->chemin($variante),
                "cartes/fond-{$variante->name}.png",
            ]);
        }
    }

    // -----------------------------------------------------------------------

    private function chemin(VarianteCarte $variante): string
    {
        return "cartes/fond-{$variante->name}-".self::MOTIF.'.png';
    }

    private function peindre(VarianteCarte $variante): string
    {
        $image = imagecreatetruecolor(self::RENDU_L, self::RENDU_H);
        imagealphablending($image, false);

        if ($variante === VarianteCarte::Verte) {
            imagefill($image, 0, 0, $this->couleur($image, self::BAOBAB));
            $this->claustraPlein($image, self::BAOBAB, self::IVOIRE, 0.14, 0.18);
        } else {
            imagefill($image, 0, 0, $this->couleur($image, self::IVOIRE));
            $this->claustraEnCoin($image);
        }

        $this->lisiere($image);

        ob_start();
        imagepng($image, null, 9);
        $binaire = (string) ob_get_clean();

        imagedestroy($image);

        return $binaire;
    }

    /**
     * Couverture d'une tuile de claustra, de 0 à 1, pixel par pixel.
     *
     * Anticrénelage ANALYTIQUE : la couverture d'un pixel par un trait est
     * la part de sa largeur que le trait recouvre, soit demi-épaisseur + ½ −
     * distance au cercle, bornée à [0, 1]. Quatre quarts de cercle par tuile,
     * centrés aux coins ; recopiées, les tuiles forment des cercles entiers
     * qui se touchent — la trame du claustra.
     *
     * @return array{0: list<list<float>>, 1: list<list<float>>} [traits, points]
     */
    private function tuile(int $cote): array
    {
        $rayon = $cote / 2;
        $demiTrait = self::TRAIT * $cote / 2;
        $rayonPoint = self::POINT * $cote;
        $coins = [[0, 0], [$cote, 0], [0, $cote], [$cote, $cote]];

        $traits = [];
        $points = [];

        for ($y = 0; $y < $cote; $y++) {
            for ($x = 0; $x < $cote; $x++) {
                $px = $x + 0.5;
                $py = $y + 0.5;

                // Union des quatre arcs : 1 − produit des parts non couvertes.
                $libre = 1.0;
                foreach ($coins as [$cx, $cy]) {
                    $d = abs(hypot($px - $cx, $py - $cy) - $rayon);
                    $libre *= 1 - max(0.0, min(1.0, $demiTrait + 0.5 - $d));
                }

                $traits[$y][$x] = 1 - $libre;
                $points[$y][$x] = max(0.0, min(1.0, $rayonPoint + 0.5 - hypot($px - $rayon, $py - $rayon)));
            }
        }

        return [$traits, $points];
    }

    /** Claustra sur toute la page : une tuile peinte, recopiée partout. */
    private function claustraPlein(\GdImage $image, string $fond, string $encre, float $opTrait, float $opPoint): void
    {
        $cote = self::TUILE_VERT;
        [$traits, $points] = $this->tuile($cote);

        $tuile = imagecreatetruecolor($cote, $cote);
        imagealphablending($tuile, false);

        for ($y = 0; $y < $cote; $y++) {
            for ($x = 0; $x < $cote; $x++) {
                $part = max($traits[$y][$x] * $opTrait, $points[$y][$x] * $opPoint);
                imagesetpixel($tuile, $x, $y, $this->melange($tuile, $fond, $encre, $part));
            }
        }

        // Calage sur le trait de coupe : la première tuile commence au coin
        // de la carte, celles d'avant débordent dans les fonds perdus.
        $origine = (int) round(self::FONDS_PERDUS * self::PX_MM);
        $depart = $origine - $cote * (int) ceil($origine / $cote);

        for ($y = $depart; $y < self::RENDU_H; $y += $cote) {
            for ($x = $depart; $x < self::RENDU_L; $x += $cote) {
                imagecopy($image, $tuile, $x, $y, 0, 0, $cote, $cote);
            }
        }

        imagedestroy($tuile);
    }

    /**
     * Claustra baobab à 13 % dans le coin inférieur GAUCHE de la face ivoire.
     *
     * La maquette le pose à droite, sous un recto sans QR Code. Sur la carte
     * imprimée, le coin droit porte le panneau du QR du porteur : le claustra
     * y disparaissait dessous, et le coin gauche restait vide. À gauche, il
     * équilibre la composition — texte en haut, code en bas à droite,
     * filigrane en bas à gauche.
     *
     * La maquette l'estompe par un dégradé linéaire à 135°, dans une boîte
     * dont le bord haut restait visible — un trait net là où le motif devait
     * s'évanouir. Ici le fondu part du COIN de la carte et s'annule avant les
     * bords de la zone (40 × 30 mm) : aucune arête. Il est nul avant d'atteindre
     * la fonction du porteur, écrite en haut.
     *
     * Seul le FILIGRANE s'estompe ; le fond, lui, reste un aplat ivoire.
     */
    private function claustraEnCoin(\GdImage $image): void
    {
        $cote = self::TUILE_COIN;
        [$traits, $points] = $this->tuile($cote);

        $origine = self::FONDS_PERDUS * self::PX_MM;

        // Le coin : bord gauche de la carte coupée, juste au-dessus de la lisière.
        $coinX = self::FONDS_PERDUS * self::PX_MM;
        $coinY = (self::BAS_CARTE - self::LISIERE_MM) * self::PX_MM;
        $rayonX = 40 * self::PX_MM;
        $rayonY = 30 * self::PX_MM;

        // La zone déborde à gauche dans les fonds perdus.
        $x0 = 0;
        $y0 = (int) round($coinY - $rayonY);
        $x1 = (int) round($coinX + $rayonX);
        $y1 = (int) round($coinY);

        for ($y = $y0; $y < $y1; $y++) {
            for ($x = $x0; $x < $x1; $x++) {
                $tx = (int) fmod($x - $origine + 10 * $cote, $cote);
                $ty = (int) fmod($y - $origine + 10 * $cote, $cote);

                $couverture = max($traits[$ty][$tx] * 0.13, $points[$ty][$tx] * 0.16);
                if ($couverture <= 0.002) {
                    continue;
                }

                // Distance elliptique au coin : plein jusqu'à 40 %, nul à 100 %.
                $d = hypot(($x - $coinX) / $rayonX, ($y - $coinY) / $rayonY);
                $masque = max(0.0, min(1.0, (1 - $d) / 0.6));

                if ($masque > 0) {
                    imagesetpixel($image, $x, $y, $this->melange($image, self::IVOIRE, self::BAOBAB, $couverture * $masque));
                }
            }
        }
    }

    /**
     * La lisière tissée : losanges latérite et or sur baobab, point central.
     *
     * Une tuile de 24 × 8 unités, comme le SVG de la maquette, dessinée QUATRE
     * FOIS PLUS GRANDE puis réduite : la réduction moyenne les pixels et lisse
     * les obliques des losanges, que GD trace sans anticrénelage.
     */
    private function lisiere(\GdImage $image): void
    {
        $hauteur = (int) round(self::LISIERE_MM * self::PX_MM);   // 28 px
        $unite = $hauteur / 8;
        $largeur = (int) round(24 * $unite);                      // 84 px
        $ech = 4;

        $grande = imagecreatetruecolor($largeur * $ech, $hauteur * $ech);
        imagefill($grande, 0, 0, $this->couleur($grande, self::BAOBAB));

        $u = $unite * $ech;
        $losange = fn (float $cx) => [
            (int) round($cx * $u), 0,
            (int) round(($cx + 6) * $u), (int) round(4 * $u),
            (int) round($cx * $u), (int) round(8 * $u),
            (int) round(($cx - 6) * $u), (int) round(4 * $u),
        ];

        imagefilledpolygon($grande, $losange(6), $this->couleur($grande, self::LATERITE));
        imagefilledpolygon($grande, $losange(18), $this->couleur($grande, self::OR));
        imagefilledellipse($grande, (int) round(6 * $u), (int) round(4 * $u), (int) round(2 * $u), (int) round(2 * $u), $this->couleur($grande, self::SABLE));
        imagefilledellipse($grande, (int) round(18 * $u), (int) round(4 * $u), (int) round(2 * $u), (int) round(2 * $u), $this->couleur($grande, self::BAOBAB));

        $tuile = imagecreatetruecolor($largeur, $hauteur);
        imagecopyresampled($tuile, $grande, 0, 0, 0, 0, $largeur, $hauteur, $largeur * $ech, $hauteur * $ech);
        imagedestroy($grande);

        // La bande visible, juste au-dessus du trait de coupe bas…
        $haut = (int) round((self::BAS_CARTE - self::LISIERE_MM) * self::PX_MM);
        $origine = (int) round(self::FONDS_PERDUS * self::PX_MM);

        for ($x = $origine - $largeur * (int) ceil($origine / $largeur); $x < self::RENDU_L; $x += $largeur) {
            imagecopy($image, $tuile, $x, $haut, 0, 0, $largeur, $hauteur);
        }

        // …et son fond baobab jusqu'au bord de page, fonds perdus compris.
        imagefilledrectangle($image, 0, $haut + $hauteur, self::RENDU_L - 1, self::RENDU_H - 1, $this->couleur($image, self::BAOBAB));

        imagedestroy($tuile);
    }

    /** Mélange de deux couleurs : $part de l'encre sur le fond. */
    private function melange(\GdImage $image, string $fond, string $encre, float $part): int
    {
        [$fr, $fv, $fb] = $this->rgb($fond);
        [$er, $ev, $eb] = $this->rgb($encre);

        return imagecolorallocate(
            $image,
            (int) round($fr + ($er - $fr) * $part),
            (int) round($fv + ($ev - $fv) * $part),
            (int) round($fb + ($eb - $fb) * $part),
        );
    }

    private function couleur(\GdImage $image, string $hex): int
    {
        return imagecolorallocate($image, ...$this->rgb($hex));
    }

    /**
     * « #0D3B2E » → [13, 59, 46].
     *
     * @return array{0:int,1:int,2:int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            $hex = '0D3B2E';
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
