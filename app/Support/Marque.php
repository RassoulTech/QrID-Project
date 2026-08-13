<?php

namespace App\Support;

/**
 * LE MONOGRAMME DE LA MARQUE, calculé en un seul endroit.
 *
 * Il est apparu deux fois : dans le composant x-brand, pour l'interface, et
 * dans le gabarit d'impression, pour la carte PVC. Deux calculs du même
 * monogramme finissent toujours par diverger — et ici, la divergence se
 * constaterait sur des cartes déjà sorties de l'imprimerie.
 *
 * IL EST CALCULÉ, JAMAIS ÉCRIT EN DUR. « QI » n'est pas une constante : c'est
 * ce que donne « QrID » aujourd'hui. Le jour où APP_NAME change, le
 * monogramme suit — sur la navbar, dans les e-mails et sur la carte, sans
 * qu'aucun fichier ne soit à retoucher.
 */
final class Marque
{
    /**
     * Deux lettres au maximum.
     *
     * Deux règles, dans cet ordre :
     *
     *   · un nom en PLUSIEURS MOTS donne les initiales des deux premiers —
     *     « Sama Kart » → SK ;
     *   · un nom en UN SEUL MOT donne ses deux premières capitales —
     *     « QrID » → QI, ce qui est bien le monogramme voulu.
     *
     * Le repli sur la première lettre couvre le cas d'un nom sans aucune
     * capitale, où les deux règles resteraient muettes.
     */
    public static function monogramme(?string $nom = null): string
    {
        $nom = $nom ?? (string) config('app.name');

        $mots = preg_split('/[\s\-_]+/', $nom, -1, PREG_SPLIT_NO_EMPTY) ?: [$nom];

        if (count($mots) >= 2) {
            return mb_strtoupper(mb_substr($mots[0], 0, 1).mb_substr($mots[1], 0, 1));
        }

        preg_match_all('/\p{Lu}/u', $nom, $capitales);

        $lettres = $capitales[0] ?: [mb_substr($nom, 0, 1)];

        return mb_strtoupper(implode('', array_slice($lettres, 0, 2)));
    }
}
