<?php

namespace App\Support;

/**
 * LES COULEURS OFFICIELLES DES PLATEFORMES.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI ELLES SORTENT DU COMPOSANT D'ICÔNE
 * ═══════════════════════════════════════════════════════════════════════
 * Elles vivaient dans x-social-icon, posées sur le <svg> en variable CSS.
 * Cela suffisait tant que seule l'icône en avait besoin.
 *
 * La tuile qui l'entoure en a besoin aussi : son fond est une version très
 * pâle de cette même couleur, et son survol y bascule franchement. Une
 * variable posée sur l'enfant ne remonte pas au parent — c'est le sens de
 * la cascade. Le catalogue doit donc être lisible AVANT le rendu de
 * l'icône, par qui en a besoin.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE NE SONT PAS DES COULEURS DE NOTRE PALETTE
 * ═══════════════════════════════════════════════════════════════════════
 * Ce sont celles des marques concernées, et elles ne se discutent pas : un
 * WhatsApp qui ne serait pas #25D366 ne se reconnaît plus d'un coup d'œil,
 * ce qui est exactement la fonction d'un logo. Elles ne suivent donc ni le
 * thème, ni les jetons de la marque.
 *
 * Les trois dernières entrées ne sont pas des plateformes : ce sont les
 * moyens de contact directs, qui partagent la grille. Elles portent les
 * couleurs de QrID, faute d'en avoir de propres.
 */
final class CouleursPlateformes
{
    /** @var array<string, string> */
    private const COULEURS = [
        'linkedin' => '#0A66C2',
        'facebook' => '#1877F2',
        'instagram' => '#E4405F',
        'tiktok' => '#000000',
        'x' => '#000000',
        'youtube' => '#FF0000',
        'github' => '#181717',
        'whatsapp' => '#25D366',
        'telegram' => '#26A5E4',
        'snapchat' => '#F7CE00',
        'pinterest' => '#BD081C',
        'behance' => '#1769FF',
        'dribbble' => '#EA4C89',

        /*
         | Moyens de contact directs — les couleurs de la marque.
         |
         | LE VERT ACCENT, ET NON LE VERT FONCÉ. Le vert foncé (#0B3B2E) est
         | fait pour porter du blanc sur fond clair ; posé en LOGO sur une
         | tuile de thème sombre, il disparaît dans le fond. Constaté sur la
         | tuile « Appeler », dont le combiné devenait invisible.
         |
         | #1E9E7A tient dans les deux thèmes : c'est la seule teinte de la
         | palette qui le fasse, et c'est pour cela qu'elle est l'accent.
         */
        'telephone' => '#1E9E7A',
        'localisation' => '#1E9E7A',
        'email' => '#1E9E7A',
    ];

    /**
     * La couleur d'une plateforme, ou le vert de la marque à défaut.
     *
     * Jamais null : une tuile sans couleur serait une tuile sans fond, et le
     * repli discret vaut mieux qu'un trou dans la grille.
     */
    public static function pour(?string $plateforme): string
    {
        return self::COULEURS[mb_strtolower((string) $plateforme)] ?? '#0B3B2E';
    }

    /** @return array<string, string> */
    public static function toutes(): array
    {
        return self::COULEURS;
    }
}
