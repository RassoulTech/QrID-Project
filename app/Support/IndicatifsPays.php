<?php

namespace App\Support;

use App\Rules\SenegalPhone;

/**
 * LE CATALOGUE DES INDICATIFS TÉLÉPHONIQUES.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI L'AFRIQUE DE L'OUEST EN TÊTE
 * ═══════════════════════════════════════════════════════════════════════
 * Une liste alphabétique mondiale met l'Afghanistan en premier et le Sénégal
 * en cent-quatre-vingtième position. Sur un produit dont la quasi-totalité des
 * clients est sénégalaise, cela transforme un champ en corvée de défilement.
 *
 * Les huit pays de la zone UEMOA viennent donc d'abord — ce sont ceux d'où
 * viennent les correspondants, les diasporas et les premiers clients hors
 * frontières. Le reste suit par ordre alphabétique.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA LONGUEUR NATIONALE EST DÉCLARÉE, PAS DEVINÉE
 * ═══════════════════════════════════════════════════════════════════════
 * Valider « au moins six chiffres » accepte un numéro tronqué, et le client ne
 * l'apprend qu'au premier appel qui n'aboutit pas. Chaque pays porte donc la
 * ou les longueurs réellement en service.
 */
class IndicatifsPays
{
    public const DEFAUT = 'SN';

    /**
     * code ISO => [nom, indicatif, drapeau, longueurs nationales admises]
     *
     * @return array<string, array{0:string,1:string,2:string,3:list<int>}>
     */
    public static function catalogue(): array
    {
        return [
            // ─── Afrique de l'Ouest, en tête ───
            'SN' => ['Sénégal', '+221', '🇸🇳', [9]],
            'CI' => ['Côte d’Ivoire', '+225', '🇨🇮', [10]],
            'ML' => ['Mali', '+223', '🇲🇱', [8]],
            'BF' => ['Burkina Faso', '+226', '🇧🇫', [8]],
            'NE' => ['Niger', '+227', '🇳🇪', [8]],
            'TG' => ['Togo', '+228', '🇹🇬', [8]],
            'BJ' => ['Bénin', '+229', '🇧🇯', [8, 10]],
            'GW' => ['Guinée-Bissau', '+245', '🇬🇼', [7, 9]],
            'GN' => ['Guinée', '+224', '🇬🇳', [9]],
            'MR' => ['Mauritanie', '+222', '🇲🇷', [8]],
            'GM' => ['Gambie', '+220', '🇬🇲', [7]],
            'NG' => ['Nigeria', '+234', '🇳🇬', [10]],
            'GH' => ['Ghana', '+233', '🇬🇭', [9]],

            // ─── Reste du monde, par ordre alphabétique ───
            'DE' => ['Allemagne', '+49', '🇩🇪', [10, 11]],
            'SA' => ['Arabie saoudite', '+966', '🇸🇦', [9]],
            'BE' => ['Belgique', '+32', '🇧🇪', [9]],
            'CM' => ['Cameroun', '+237', '🇨🇲', [9]],
            'CA' => ['Canada', '+1', '🇨🇦', [10]],
            'CN' => ['Chine', '+86', '🇨🇳', [11]],
            'CD' => ['Congo (RDC)', '+243', '🇨🇩', [9]],
            'ES' => ['Espagne', '+34', '🇪🇸', [9]],
            'US' => ['États-Unis', '+1', '🇺🇸', [10]],
            'FR' => ['France', '+33', '🇫🇷', [9]],
            'GA' => ['Gabon', '+241', '🇬🇦', [8]],
            'IT' => ['Italie', '+39', '🇮🇹', [9, 10]],
            'MA' => ['Maroc', '+212', '🇲🇦', [9]],
            'PT' => ['Portugal', '+351', '🇵🇹', [9]],
            'CG' => ['République du Congo', '+242', '🇨🇬', [9]],
            'GB' => ['Royaume-Uni', '+44', '🇬🇧', [10]],
            'CH' => ['Suisse', '+41', '🇨🇭', [9]],
            'TD' => ['Tchad', '+235', '🇹🇩', [8]],
            'TN' => ['Tunisie', '+216', '🇹🇳', [8]],
            'TR' => ['Turquie', '+90', '🇹🇷', [10]],
            'AE' => ['Émirats arabes unis', '+971', '🇦🇪', [9]],
        ];
    }

    /** Libellés prêts pour un <select> : « 🇸🇳 Sénégal (+221) ». */
    public static function options(): array
    {
        $options = [];

        foreach (self::catalogue() as $code => [$nom, $indicatif, $drapeau]) {
            $options[$code] = "{$drapeau} {$nom} ({$indicatif})";
        }

        return $options;
    }

    public static function existe(?string $code): bool
    {
        return $code !== null && array_key_exists(mb_strtoupper($code), self::catalogue());
    }

    public static function indicatif(string $code): ?string
    {
        return self::catalogue()[mb_strtoupper($code)][1] ?? null;
    }

    /** @return list<int> */
    public static function longueurs(string $code): array
    {
        return self::catalogue()[mb_strtoupper($code)][3] ?? [];
    }

    /**
     * Assemble un numéro international à partir d'un pays et d'une saisie.
     *
     * LE SÉNÉGAL GARDE SA RÈGLE PROPRE. Elle vérifie les préfixes mobiles
     * réellement attribués (70, 75, 76, 77, 78) — un contrôle de longueur seul
     * accepterait « 123456789 », qui n'appellera jamais personne.
     */
    public static function normaliser(?string $code, mixed $saisie): ?string
    {
        $code = mb_strtoupper((string) ($code ?: self::DEFAUT));

        if (! self::existe($code)) {
            return null;
        }

        if ($code === 'SN') {
            return SenegalPhone::normalize($saisie);
        }

        $indicatif = self::indicatif($code);
        $chiffres = preg_replace('/\D+/', '', (string) $saisie) ?? '';

        // On retire l'indicatif s'il a été ressaisi, puis le zéro de courtoisie.
        $sansPlus = ltrim((string) $indicatif, '+');

        if (str_starts_with($chiffres, $sansPlus) && strlen($chiffres) > strlen($sansPlus)) {
            $chiffres = substr($chiffres, strlen($sansPlus));
        }

        /*
         | LE ZÉRO INITIAL N'EST PAS TOUJOURS UN ZÉRO DE COURTOISIE.
         |
         | En France ou au Sénégal, « 06… » et « 077… » portent un zéro de
         | départ qu'on retire avant l'indicatif. En Côte d'Ivoire, « 07 01 02
         | 03 04 » compte DIX chiffres, zéro compris : le retirer produit un
         | numéro à neuf chiffres que le pays n'attribue pas.
         |
         | On essaie donc les deux formes et l'on garde celle dont la longueur
         | existe réellement — plutôt que d'appliquer une convention française
         | à tout un continent.
         */
        $longueurs = self::longueurs($code);

        foreach ([$chiffres, ltrim($chiffres, '0')] as $candidat) {
            if (in_array(strlen($candidat), $longueurs, true)) {
                return $indicatif.$candidat;
            }
        }

        return null;
    }
}
