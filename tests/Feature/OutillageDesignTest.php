<?php

namespace Tests\Feature;

use App\Support\Design\Analyseur;
use App\Support\Design\Contraste;
use Tests\TestCase;

/**
 * L'OUTILLAGE DE DESIGN SE TESTE COMME LE RESTE.
 *
 * Une commande de garde-fou dans laquelle on ne peut pas avoir confiance
 * est pire qu'aucune : elle donne le sentiment d'être protégé. Le relevé
 * manuel a rendu trois chiffres différents avant celui-ci ; ces tests
 * fixent le comportement attendu pour que la quatrième version ne dérive
 * pas à son tour.
 */
class OutillageDesignTest extends TestCase
{
    // =====================================================================
    // LE CALCUL DE CONTRASTE
    // =====================================================================

    /** Les deux extrêmes, dont la valeur est connue de tous. */
    public function test_les_ratios_de_reference(): void
    {
        $this->assertEqualsWithDelta(21.0, Contraste::ratio('#000000', '#FFFFFF'), 0.01);
        $this->assertEqualsWithDelta(1.0, Contraste::ratio('#123456', '#123456'), 0.01);
    }

    /** Le ratio est symétrique : l'ordre des arguments ne change rien. */
    public function test_le_ratio_est_symetrique(): void
    {
        $a = Contraste::ratio('#0B3B2E', '#FFFFFF');
        $b = Contraste::ratio('#FFFFFF', '#0B3B2E');

        $this->assertEqualsWithDelta($a, $b, 0.0001);
    }

    /** La notation courte vaut la longue. */
    public function test_le_hex_court_est_compris(): void
    {
        $this->assertSame(Contraste::luminance('#FFF'), Contraste::luminance('#FFFFFF'));
    }

    /**
     * L'APLATISSEMENT — le piège qui a fait échouer treize couleurs.
     *
     * Une teinte à 16 % sur une surface sombre donne une couleur PLUS
     * CLAIRE que la surface. Mesurer contre la surface nue rend un chiffre
     * faux dans le sens rassurant.
     */
    public function test_une_couche_semi_transparente_est_aplatie(): void
    {
        $nu = '#1D2B26';
        $teinte = Contraste::aplatir('#1FBC8A', 0.16, $nu);

        $this->assertNotSame($nu, $teinte);
        $this->assertGreaterThan(
            Contraste::luminance($nu),
            Contraste::luminance($teinte),
            'Un vert clair posé à 16 % doit éclaircir la surface.'
        );

        // Et le contraste contre le fond teinté est FORCÉMENT plus faible
        // que contre la surface nue : c'est tout l'objet de la mesure.
        $this->assertLessThan(
            Contraste::ratio('#1FBC8A', $nu),
            Contraste::ratio('#1FBC8A', $teinte)
        );
    }

    /** Une opacité de 1 ne change rien ; une opacité de 0 rend le fond. */
    public function test_les_opacites_extremes(): void
    {
        $this->assertSame('#1FBC8A', Contraste::aplatir('#1FBC8A', 1.0, '#000000'));
        $this->assertSame('#1D2B26', Contraste::aplatir('#1FBC8A', 0.0, '#1D2B26'));
    }

    // =====================================================================
    // LES COMMANDES
    // =====================================================================

    public function test_design_contraste_passe_sur_les_tokens_livres(): void
    {
        $this->artisan('design:contraste')->assertSuccessful();
    }

    public function test_design_check_respecte_ses_plafonds(): void
    {
        $this->artisan('design:check')->assertSuccessful();
    }

    /**
     * LE CLIQUET DOIT MORDRE.
     *
     * Un plafond qu'on peut dépasser sans que rien ne se passe n'est pas
     * un plafond. On l'abaisse artificiellement et on vérifie que la
     * commande échoue.
     */
    public function test_le_cliquet_fait_echouer_la_commande(): void
    {
        config(['design.plafond.valeurs' => 0]);

        $this->artisan('design:check')->assertFailed();
    }

    public function test_design_audit_produit_un_releve(): void
    {
        $this->artisan('design:audit --sans-fichier')->assertSuccessful();
    }

    // =====================================================================
    // L'ANALYSEUR
    // =====================================================================

    /**
     * Les fichiers sources ont le droit de porter des valeurs — c'est
     * leur raison d'être. Les compter reviendrait à demander à la source
     * de vérité de ne pas contenir de vérité.
     */
    public function test_les_fichiers_sources_sont_exemptes(): void
    {
        $resultat = (new Analyseur)->analyser(base_path());
        $constats = $resultat['constats'];

        foreach (['couleur', 'longueur', 'duree'] as $categorie) {
            foreach ($constats[$categorie] ?? [] as $item) {
                $this->assertNotContains(
                    basename($item['fichier']),
                    Analyseur::SOURCES,
                    "Une valeur de {$item['fichier']} ne devrait pas être comptée."
                );
            }
        }
    }

    /**
     * LES E-MAILS SONT EXEMPTÉS, ET CE N'EST PAS UNE FACILITÉ.
     *
     * Aucun client de messagerie ne lit une feuille de style externe, et
     * beaucoup ignorent les propriétés CSS personnalisées. Le style en
     * ligne y est imposé par la cible.
     *
     * Ce test a une histoire : sous Windows, la comparaison de chemins
     * échouait sur le séparateur, les 14 gabarits étaient comptés, et le
     * total annonçait 191 styles en ligne au lieu de 98.
     */
    public function test_les_gabarits_d_email_ne_sont_pas_comptes(): void
    {
        $resultat = (new Analyseur)->analyser(base_path());

        foreach ($resultat['constats']['style-en-ligne'] ?? [] as $item) {
            $this->assertStringNotContainsString(
                'views/emails/',
                $item['fichier'],
                'Le style en ligne des e-mails est imposé par la cible.'
            );
        }
    }

    /** Les chemins rendus sont relatifs, et en barres obliques. */
    public function test_les_chemins_sont_relatifs(): void
    {
        $resultat = (new Analyseur)->analyser(base_path());
        $premier = null;

        foreach ($resultat['constats'] as $items) {
            if ($items !== []) {
                $premier = $items[0]['fichier'];
                break;
            }
        }

        $this->assertNotNull($premier, 'L\'analyseur ne relève plus rien du tout.');
        $this->assertStringStartsWith('resources/', $premier);
        $this->assertStringNotContainsString('\\', $premier);
    }
}
