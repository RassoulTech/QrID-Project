<?php

namespace App\Support\Design;

/**
 * LE CALCUL DE CONTRASTE, SELON WCAG 2.1.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE PIÈGE QUI A FAIT ÉCHOUER TREIZE COULEURS
 * ═══════════════════════════════════════════════════════════════════════
 * La première palette vérifiait chaque teinte contre la surface NUE. Or
 * un badge pose un fond TEINTÉ par-dessus — la même teinte à 16 %. Ce
 * fond éclaircit la surface, et le texte perd du contraste contre son
 * PROPRE fond.
 *
 * Le fond dépend de la teinte, qui dépend du fond : il faut aplatir la
 * couche semi-transparente avant de mesurer. C'est ce que fait
 * `aplatir()`, et c'est la raison d'être de cette classe.
 */
class Contraste
{
    /** Texte normal. */
    public const SEUIL_TEXTE = 4.5;

    /** Texte large (≥24px, ou ≥18,66px en gras) et tracés d'interface. */
    public const SEUIL_LARGE = 3.0;

    /** @return array{0:int,1:int,2:int} */
    public static function versRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function versHex(float $r, float $v, float $b): string
    {
        return sprintf('#%02X%02X%02X', (int) round($r), (int) round($v), (int) round($b));
    }

    /** La luminance relative, formule WCAG. */
    public static function luminance(string $hex): float
    {
        [$r, $v, $b] = self::versRgb($hex);

        $canal = static function (int $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $canal($r) + 0.7152 * $canal($v) + 0.0722 * $canal($b);
    }

    public static function ratio(string $avant, string $arriere): float
    {
        $a = self::luminance($avant);
        $b = self::luminance($arriere);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /**
     * Aplatit une couleur semi-transparente sur son fond.
     *
     * Sans cette étape, un badge dont le fond est sa propre teinte à 16 %
     * est mesuré contre la surface nue — et le chiffre obtenu est faux
     * dans le sens rassurant, celui qui ne se corrige jamais.
     */
    public static function aplatir(string $avant, float $alpha, string $arriere): string
    {
        [$ar, $av, $ab] = self::versRgb($avant);
        [$fr, $fv, $fb] = self::versRgb($arriere);

        return self::versHex(
            $ar * $alpha + $fr * (1 - $alpha),
            $av * $alpha + $fv * (1 - $alpha),
            $ab * $alpha + $fb * (1 - $alpha),
        );
    }
}
