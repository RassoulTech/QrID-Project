<?php

namespace Tests\Feature;

use App\Models\Payment;
use Tests\TestCase;

/**
 * LES MARQUES D'OPÉRATEURS — un logo déposé doit s'afficher, ou le dire.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS PROTÈGENT
 * ═══════════════════════════════════════════════════════════════════════
 * Le composant x-operator-mark promet qu'il suffit de poser un fichier dans
 * public/images/operateurs/ pour remplacer la pastille par le vrai logo, sans
 * toucher au code. Cette promesse est commode et silencieuse : quand elle n'est
 * pas tenue, il ne se passe RIEN — pas d'erreur, pas de page blanche, juste la
 * pastille qui reste, et personne ne sait si le fichier est mauvais, mal nommé,
 * ou si le composant l'ignore.
 *
 * C'est arrivé aux deux premiers logos reçus. « orange money.jpeg » portait une
 * espace au lieu d'un souligné, et l'extension .jpeg n'était pas dans la liste
 * des formats reconnus. Les deux fichiers étaient au bon endroit, tous les deux
 * invisibles, et l'écran de paiement était identique à la veille.
 *
 * Ces tests transforment ce silence en échec.
 */
class OperatorMarkTest extends TestCase
{
    /**
     * L'ordre exact du composant. Le JPEG est dernier parce qu'il ignore la
     * transparence ; il est présent parce qu'un dépôt muet coûte plus cher
     * qu'un fond blanc.
     */
    private const EXTENSIONS = ['svg', 'png', 'webp', 'jpg', 'jpeg'];

    private const DOSSIER = 'images/operateurs';

    /** Le fichier servi pour une méthode, ou null si elle garde sa pastille. */
    private function fichierDe(string $methode): ?string
    {
        foreach (self::EXTENSIONS as $ext) {
            $chemin = self::DOSSIER."/{$methode}.{$ext}";

            if (is_file(public_path($chemin))) {
                return $chemin;
            }
        }

        return null;
    }

    private function rendu(string $methode): string
    {
        return $this->blade('<x-operator-mark :methode="$methode" />', ['methode' => $methode]);
    }

    /**
     * LE CŒUR DU SUJET : ce qui est sur le disque arrive à l'écran.
     *
     * Le test ne fixe pas la liste des logos attendus — il constate ce qui est
     * déposé et exige que ce soit servi. Déposer free_money.svg demain suffira
     * donc à le faire couvrir, sans toucher à ce fichier.
     */
    public function test_every_deposited_logo_is_actually_served(): void
    {
        $servis = 0;

        foreach (array_keys(Payment::METHODS) as $methode) {
            $fichier = $this->fichierDe($methode);

            if ($fichier === null) {
                continue;
            }

            $this->assertStringContainsString(
                $fichier,
                $this->rendu($methode),
                "Le logo {$fichier} est sur le disque mais n'apparaît pas à l'écran."
            );

            $servis++;
        }

        $this->assertGreaterThan(0, $servis, 'Aucun logo d\'opérateur n\'est déposé : le dossier a été vidé.');
    }

    /**
     * L'INVERSE COMPTE AUTANT : sans fichier, la pastille tient le rôle.
     *
     * Une case vide sur un écran de paiement inquiète bien plus qu'une pastille
     * aux couleurs de l'opérateur.
     */
    public function test_a_method_without_its_logo_keeps_its_pastille(): void
    {
        foreach (array_keys(Payment::METHODS) as $methode) {
            if ($this->fichierDe($methode) !== null) {
                continue;
            }

            $this->assertStringContainsString(
                'pay-option__pastille',
                $this->rendu($methode),
                "La méthode {$methode} n'a ni logo ni pastille : sa case est vide."
            );
        }
    }

    /**
     * AUCUN FICHIER MUET DANS LE DOSSIER.
     *
     * Un fichier mal nommé — « orange money.jpeg », « wave-logo.png » — est
     * pire qu'un fichier absent : on le voit dans le dossier, on croit le
     * travail fait, et l'écran ne change pas. Il est aussi expédié en
     * production, où il ne sert rien.
     */
    public function test_no_image_in_the_folder_is_silently_ignored(): void
    {
        $connus = array_keys(Payment::METHODS);

        foreach (glob(public_path(self::DOSSIER.'/*')) as $chemin) {
            $nom = basename($chemin);
            $ext = strtolower(pathinfo($chemin, PATHINFO_EXTENSION));

            // La notice du dossier n'est pas un logo.
            if ($ext === 'md') {
                continue;
            }

            $this->assertContains(
                $ext,
                self::EXTENSIONS,
                "{$nom} : l'extension .{$ext} n'est pas reconnue par x-operator-mark, ce fichier ne s'affichera jamais."
            );

            $this->assertContains(
                pathinfo($chemin, PATHINFO_FILENAME),
                $connus,
                "{$nom} : le nom doit être celui de la méthode de paiement (".implode(', ', $connus).'), sans espace ni majuscule.'
            );
        }
    }

    /**
     * CADRAGE : un logo est un carré serré sur son symbole.
     *
     * La boîte fait 40 px de côté. Une bannière avec de larges marges y devient
     * un timbre illisible, un verrouillage « symbole + nom » y écrase le nom sur
     * quatre pixels de haut. Le premier wave.jpeg reçu était une bannière
     * 1200 × 630 dont l'icône n'occupait que le tiers central : conforme à tout
     * ce que le composant exigeait, et inutilisable.
     */
    public function test_deposited_logos_are_square_and_dense_enough(): void
    {
        foreach (array_keys(Payment::METHODS) as $methode) {
            $fichier = $this->fichierDe($methode);

            // Un SVG se met à l'échelle sans perte et n'a pas de dimension à
            // mesurer ici : le cadrage se lit dans son viewBox, pas en pixels.
            if ($fichier === null || str_ends_with($fichier, '.svg')) {
                continue;
            }

            [$l, $h] = getimagesize(public_path($fichier));

            $this->assertGreaterThanOrEqual(
                80,
                min($l, $h),
                "{$fichier} : {$l}×{$h}, trop petit. La boîte fait 40 px et un écran de téléphone en compte 3 réels par point."
            );

            // 0,85 à 1,18 : assez large pour un logo officiel qui n'est pas
            // parfaitement carré, assez étroit pour refuser un verrouillage
            // complet — celui d'Orange Money faisait 1,97.
            $this->assertGreaterThanOrEqual(0.85, $l / $h, "{$fichier} : {$l}×{$h}, plus haut que large. Recadrez sur le symbole.");
            $this->assertLessThanOrEqual(1.18, $l / $h, "{$fichier} : {$l}×{$h}, plus large que haut. Recadrez sur le symbole, sans le nom.");
        }
    }
}
