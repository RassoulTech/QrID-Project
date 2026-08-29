<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * NOMBRES, MONTANTS ET DATES — écrits selon la langue affichée.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CE N'EST PAS UN DÉTAIL COSMÉTIQUE
 * ═══════════════════════════════════════════════════════════════════════
 * Le français sépare les milliers par une espace : « 15 000 FCFA ».
 * L'anglais les sépare par une virgule : « 15,000 FCFA ».
 *
 * Or « 15 000 » lu par un anglophone se lit volontiers comme DEUX nombres,
 * et « 15,000 » lu par un francophone se lit comme quinze virgule zéro.
 * Sur une page de paiement, l'ambiguïté ne porte pas sur la mise en forme :
 * elle porte sur la somme qu'on s'apprête à payer.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE FCFA NE SE TRADUIT PAS, ET NE SE CONVERTIT PAS
 * ═══════════════════════════════════════════════════════════════════════
 * La devise reste « FCFA » dans les deux langues. Ce n'est pas un oubli :
 * c'est la monnaie réellement débitée. Afficher un équivalent en euros ou
 * en dollars à un visiteur anglophone laisserait croire qu'il peut payer
 * dans cette monnaie — ce qui est faux, et ne se découvrirait qu'à
 * l'échec du paiement.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES SÉPARATEURS VIENNENT DES FICHIERS DE LANGUE
 * ═══════════════════════════════════════════════════════════════════════
 * Ils ne sont pas codés en dur dans un match() sur la locale. Ajouter une
 * troisième langue ne demandera donc pas de revenir ici : le fichier de
 * langue portera ses propres séparateurs, comme il porte ses phrases.
 */
final class Formats
{
    /**
     * Un montant en francs CFA, séparateurs selon la langue.
     *
     * La devise est collée au nombre par une espace insécable : un montant
     * coupé en fin de ligne entre « 15 000 » et « FCFA » se lit mal, et
     * sur un reçu il se lit deux fois.
     */
    public static function montant(int|float|string|null $valeur, bool $avecDevise = true): string
    {
        $nombre = self::nombre($valeur);

        return $avecDevise ? $nombre."\u{202F}".__('common.formats.devise') : $nombre;
    }

    /** Un nombre entier, séparateurs de milliers selon la langue. */
    public static function nombre(int|float|string|null $valeur, int $decimales = 0): string
    {
        return number_format(
            (float) ($valeur ?? 0),
            $decimales,
            __('common.formats.separateur_decimal'),
            __('common.formats.separateur_milliers')
        );
    }

    /** Une date longue : « 12 janvier 2026 » / « January 12, 2026 ». */
    public static function date(?CarbonInterface $date): string
    {
        return $date?->locale(app()->getLocale())
            ->translatedFormat(__('common.formats.date_longue')) ?? '';
    }

    /** Une date avec l'heure. */
    public static function dateHeure(?CarbonInterface $date): string
    {
        return $date?->locale(app()->getLocale())
            ->translatedFormat(__('common.formats.date_heure')) ?? '';
    }

    /** Une date avec le jour de la semaine. */
    public static function dateComplete(?CarbonInterface $date): string
    {
        return $date?->locale(app()->getLocale())
            ->translatedFormat(__('common.formats.date_complete')) ?? '';
    }
}
