<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * LES CIBLES TACTILES — vérifiées sur la CASCADE, pas sur une déclaration.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * L'ERREUR QUE CE FICHIER EMPÊCHE DE REFAIRE
 * ═══════════════════════════════════════════════════════════════════════════
 * `_admin.scss` déclare `height: 35px` sur `.adm-btn` et `height: 34px` sur
 * `.pagin__lien`. Un grep sur ce fichier désigne donc deux violations de la
 * loi 5 — 44px minimum pour une cible tactile.
 *
 * Ce sont deux FAUX POSITIFS. `_socle.scss` déclare
 * `.adm-btn { @extend %bouton; }`, et `%bouton` porte
 * `min-height: $tactile-min`. Le socle est importé APRÈS — délibérément,
 * c'est écrit en toutes lettres dans `app.scss` — donc il gagne par ordre de
 * cascade, et `min-height` l'emporte sur `height`.
 *
 * Les deux cibles font 44px à l'écran, et les faisaient déjà.
 *
 * « Corriger » ces faux défauts par une règle locale dans `_admin.scss`
 * n'aurait rien changé au rendu — le socle gagne — mais aurait installé une
 * bombe : le jour où quelqu'un déplace un import, la règle locale prendrait
 * le dessus et RAMÈNERAIT les boutons à 35px, en croyant appliquer la loi.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ON VÉRIFIE DONC LE CSS COMPILÉ, PAS LES SOURCES
 * ═══════════════════════════════════════════════════════════════════════════
 * C'est la seule lecture qui dise la vérité : elle porte l'ordre d'import,
 * les `@extend` résolus et la cascade telle que le navigateur la verra. Une
 * feuille source, isolément, ne dit rien de ce qui s'affiche.
 */
class CiblesTactilesTest extends TestCase
{
    /**
     * Les sélecteurs qui doivent porter une hauteur tactile, et le fichier
     * qui la leur donne.
     *
     * @return array<string, string>
     */
    private function ciblesGaranties(): array
    {
        return [
            '.adm-btn' => 'boutons de l\'administration',
            '.pagin__lien' => 'liens de pagination',
        ];
    }

    /** La feuille réellement servie au navigateur. */
    private function feuilleCompilee(): string
    {
        $feuilles = glob(public_path('build/assets/app-*.css')) ?: [];

        if ($feuilles === []) {
            $this->markTestSkipped('Aucune feuille compilée : lancez `npm run build`.');
        }

        return (string) file_get_contents($feuilles[0]);
    }

    /**
     * AUCUNE RÈGLE NE RAMÈNE UNE CIBLE SOUS 44px.
     *
     * On ne cherche pas la déclaration qui donne 44px — elle peut venir d'un
     * `@extend`, d'une variable, d'un raccourci. On cherche l'inverse : une
     * règle qui poserait un `min-height` INFÉRIEUR sur ces sélecteurs. C'est
     * la seule forme de défaut qui puisse réellement rétrécir une cible.
     */
    public function test_no_rule_shrinks_a_touch_target_below_the_minimum(): void
    {
        $css = $this->feuilleCompilee();
        $fautives = [];

        foreach ($this->ciblesGaranties() as $selecteur => $quoi) {
            /*
             | LA FRONTIÈRE DE SÉLECTEUR EST INDISPENSABLE.
             |
             | `.adm-btn` est un préfixe de `.adm-btn--sm`. Sans la borne
             | ci-dessous, le motif capturait la variante PETITE — 38px,
             | un choix délibéré du produit — et la signalait comme une
             | violation sur le bouton normal.
             |
             | Un nom de classe se termine par une virgule, une accolade,
             | une espace ou un deux-points. Rien d'autre.
             */
            $motif = '/[^{}]*'.preg_quote($selecteur, '/').'(?=[,{\s:])[^{}]*\{[^}]*\}/';

            if (! preg_match_all($motif, $css, $blocs)) {
                $this->fail("Le sélecteur « {$selecteur} » n'existe plus dans la feuille compilée.");
            }

            foreach ($blocs[0] as $bloc) {
                if (preg_match('/min-height:\s*(\d+)px/', $bloc, $trouve)
                    && (int) $trouve[1] < 44) {
                    $fautives[] = $quoi.' : min-height '.$trouve[1].'px';
                }
            }
        }

        $this->assertSame([], array_unique($fautives),
            'Ces cibles descendent sous les 44px de la loi 5 — le minimum en '.
            "dessous duquel on rate un bouton sur deux au pouce :\n  - ".
            implode("\n  - ", array_unique($fautives)));
    }

    /**
     * ET LA GARANTIE EXISTE BIEN.
     *
     * Le test précédent cherche une violation ; sans celui-ci, il resterait
     * vert si le socle cessait un jour d'accorder la hauteur tactile — plus
     * aucune règle ne serait fautive, et plus aucune ne protégerait.
     */
    public function test_the_touch_height_is_actually_granted_somewhere(): void
    {
        $css = $this->feuilleCompilee();

        foreach ($this->ciblesGaranties() as $selecteur => $quoi) {
            $motif = '/[^{}]*'.preg_quote($selecteur, '/').'(?=[,{\s:])[^{}]*\{[^}]*min-height:\s*44px[^}]*\}/';

            $this->assertMatchesRegularExpression($motif, $css,
                "Plus aucune règle ne donne 44px aux {$quoi} : la hauteur ".
                'tactile a disparu de la cascade.');
        }
    }
}
