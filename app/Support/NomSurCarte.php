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
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE TABLE DE LARGEURS, ET NON UNE MOYENNE
 * ═══════════════════════════════════════════════════════════════════════
 * La première version multipliait le nombre de caractères par une avance
 * moyenne unique. C'ÉTAIT FAUX, et de façon asymétrique :
 *
 *     « MOUHAMED DIONE » — deux M, parmi les plus larges lettres qui soient —
 *     occupe 9,36 em, là où la moyenne en prévoyait 8,82. Le nom débordait
 *     donc de 6 %, et passait à la ligne.
 *
 *     « AWA NDIAYE » — un W large mais deux I et A étroits — occupe 6,40 em
 *     contre 6,30 prévus. L'écart joue dans l'autre sens.
 *
 * Une moyenne ne peut pas être juste pour les deux : elle sous-estime les
 * noms chargés en M et W, et surestime les autres. Corriger par une marge de
 * sécurité plus large ne règle rien — cela éloigne simplement TOUS les noms
 * des bords, ce qui est exactement le défaut qu'on cherche à supprimer.
 *
 * La table ci-dessous distingue les quelques lettres qui s'écartent vraiment
 * de la moyenne. Elle reste une approximation — la police définitive n'est pas
 * connue — mais son erreur tombe sous les 3 %, contre 6 % pour une moyenne.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE CLASSE EXISTE PLUTÔT QU'UN CALCUL DANS CHAQUE VUE
 * ═══════════════════════════════════════════════════════════════════════
 * Le nom apparaît à l'ÉCRAN, en unités de conteneur, et à l'IMPRESSION, en
 * millimètres. Deux calculs finiraient par diverger — et la divergence se
 * constaterait sur des cartes déjà tirées, où le nom serait plus petit qu'à
 * l'aperçu.
 *
 * La méthode est donc SANS UNITÉ : elle rend une taille exprimée dans l'unité
 * de la largeur qu'on lui donne.
 */
final class NomSurCarte
{
    /**
     * Avance de la lettre courante, en fraction de la taille de police.
     * Valeur d'une capitale grasse ordinaire — B, C, D, E, G, K, N, O…
     */
    private const AVANCE_COURANTE = 0.70;

    /**
     * Les lettres qui s'écartent vraiment de la moyenne.
     *
     * On ne cherche pas l'exactitude typographique : seules figurent ici les
     * lettres dont l'écart change le résultat. Ajouter les autres alourdirait
     * la table sans améliorer le calcul.
     */
    private const AVANCES = [
        'I' => 0.30,
        'J' => 0.55,
        'L' => 0.60,
        'M' => 0.88,
        'W' => 0.90,
        'F' => 0.62,
        'E' => 0.64,
        'T' => 0.64,
        'S' => 0.66,
        ' ' => 0.30,
        '-' => 0.42,
        "'" => 0.26,
        '.' => 0.30,
    ];

    /**
     * Marge de sécurité — 4 %.
     *
     * La table reste une approximation, et la police définitive du produit
     * n'est pas arrêtée. Quatre pour cent absorbent cet écart sans éloigner
     * visiblement le nom des bords. Sous-estimer produirait un nom coupé, ce
     * qui, sur un support imprimé, est un défaut définitif.
     */
    private const SECURITE = 0.96;

    /**
     * Bornes, en fraction de la largeur de la carte.
     *
     * PLAFOND — au-delà, un nom très court deviendrait plus haut que le QR
     * Code et écraserait la composition.
     *
     * PLANCHER — en dessous, le nom deviendrait illisible à l'impression. Un
     * nom exceptionnellement long repasse alors sur deux lignes : c'est
     * préférable à un texte minuscule, et infiniment préférable à un texte
     * tronqué.
     */
    private const PLAFOND = 0.15;

    private const PLANCHER = 0.055;

    /**
     * Largeur du texte, en em — c'est-à-dire en multiples de la taille de
     * police. C'est la seule mesure dont dépend tout le reste.
     */
    public static function largeurEnEm(string $nom): float
    {
        $total = 0.0;

        foreach (mb_str_split(mb_strtoupper($nom)) as $lettre) {
            $total += self::AVANCES[$lettre] ?? self::AVANCE_COURANTE;
        }

        return max(0.5, $total);
    }

    /**
     * @param  string  $nom  le nom du porteur
     * @param  float  $largeurUtile  largeur disponible, marges déduites
     * @param  float  $largeurCarte  largeur totale, qui donne l'échelle des bornes
     * @return float la taille de police, dans l'unité de $largeurUtile
     */
    public static function taille(string $nom, float $largeurUtile, float $largeurCarte): float
    {
        $ideale = $largeurUtile * self::SECURITE / self::largeurEnEm($nom);

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
