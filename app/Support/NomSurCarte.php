<?php

namespace App\Support;

/**
 * LA TAILLE DU NOM SUR LA CARTE, calculée et non choisie.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE PROBLÈME QU'ELLE RÉSOUT
 * ═══════════════════════════════════════════════════════════════════════
 * Une taille de police fixe ne peut pas convenir : « AWA NDIAYE » et
 * « ABDOULAYE MOUHAMADOU NDIAYE » n'occupent pas la même place. Choisir une
 * valeur, c'est arbitrer entre deux défauts — un nom court perdu au milieu
 * d'une carte vide, ou un nom long qui passe à la ligne et déséquilibre toute
 * la composition.
 *
 * On calcule donc la taille à partir de la LONGUEUR du nom, pour qu'il occupe
 * la largeur disponible dans les deux cas.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE CLASSE EXISTE PLUTÔT QU'UN CALCUL DANS CHAQUE VUE
 * ═══════════════════════════════════════════════════════════════════════
 * Le nom apparaît à l'ÉCRAN, en unités de conteneur, et à l'IMPRESSION, en
 * millimètres. Deux calculs avec le même coefficient finiraient par diverger
 * — et la divergence se constaterait sur des cartes déjà tirées, où le nom
 * serait plus petit qu'à l'aperçu.
 *
 * La méthode est donc SANS UNITÉ : elle rend une taille exprimée dans l'unité
 * de la largeur qu'on lui donne. L'appelant décide s'il s'agit de cqw ou de
 * millimètres.
 */
final class NomSurCarte
{
    /**
     * Avance moyenne d'un caractère, en fraction de la taille de police.
     *
     * Mesurée à ≈ 0,58 em pour une sans-serif grasse en capitales. On retient
     * 0,63 : une marge de sécurité de 8 %, qui absorbe les noms riches en M et
     * en W sans jamais déborder. Sous-estimer ce coefficient produirait
     * exactement le défaut qu'on cherche à supprimer.
     */
    private const AVANCE = 0.63;

    /**
     * Bornes, en fraction de la largeur de la carte.
     *
     * PLAFOND — au-delà, un nom très court deviendrait plus haut que le QR
     * Code et écraserait la composition.
     *
     * PLANCHER — en dessous, le nom deviendrait illisible à l'impression. Un
     * nom exceptionnellement long repasse alors sur deux lignes : c'est
     * préférable à un texte minuscule, et infiniment préférable à un texte
     * tronqué, qui serait un défaut définitif sur un support imprimé.
     */
    private const PLAFOND = 0.15;

    private const PLANCHER = 0.055;

    /**
     * @param  string  $nom  déjà mis en capitales par l'appelant
     * @param  float  $largeurUtile  largeur disponible, marges déduites
     * @param  float  $largeurCarte  largeur totale, qui donne l'échelle des bornes
     * @return float la taille de police, dans l'unité de $largeurUtile
     */
    public static function taille(string $nom, float $largeurUtile, float $largeurCarte): float
    {
        $lettres = max(1, mb_strlen($nom));

        $ideale = $largeurUtile / ($lettres * self::AVANCE);

        return round(min(
            $largeurCarte * self::PLAFOND,
            max($largeurCarte * self::PLANCHER, $ideale)
        ), 2);
    }

    /**
     * Le nom tient-il sur une seule ligne à cette taille ?
     *
     * Faux uniquement lorsque le plancher a dû s'appliquer : c'est le signe
     * que le nom ne rentre pas, et qu'il vaut mieux le laisser s'enrouler que
     * le réduire davantage.
     */
    public static function surUneLigne(float $taille, float $largeurCarte): bool
    {
        return $taille > $largeurCarte * self::PLANCHER;
    }
}
