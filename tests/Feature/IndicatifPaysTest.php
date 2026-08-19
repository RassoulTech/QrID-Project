<?php

namespace Tests\Feature;

use App\Support\IndicatifsPays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LE SÉLECTEUR D'INDICATIF PAYS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE LE PRÉFIXE FIGÉ INTERDISAIT
 * ═══════════════════════════════════════════════════════════════════════
 * « +221 » écrit en dur rendait le produit inutilisable pour un client
 * ivoirien, un Sénégalais de la diaspora, ou toute entreprise ayant un
 * correspondant hors frontières. Le champ acceptait la saisie puis la
 * refusait, sans jamais dire que le pays était le problème.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA RÈGLE SÉNÉGALAISE EST CONSERVÉE, PAS DILUÉE
 * ═══════════════════════════════════════════════════════════════════════
 * Elle vérifie les préfixes mobiles réellement attribués — 70, 75, 76, 77,
 * 78. La remplacer par un contrôle de longueur générique aurait accepté
 * « 123456789 », qui n'appellera jamais personne. Plusieurs tests ci-dessous
 * existent pour empêcher cette simplification.
 */
class IndicatifPaysTest extends TestCase
{
    use RefreshDatabase;

    // =======================================================================
    // LE SÉNÉGAL GARDE SA RIGUEUR
    // =======================================================================

    public function test_a_senegalese_mobile_is_normalised(): void
    {
        foreach (['77 383 13 64', '+221773831364', '00221773831364', '0773831364'] as $saisie) {
            $this->assertSame(
                '+221773831364',
                IndicatifsPays::normaliser('SN', $saisie),
                "Saisie refusée à tort : {$saisie}"
            );
        }
    }

    /**
     * LE CONTRÔLE DE PRÉFIXE SURVIT.
     *
     * « 123456789 » a bien neuf chiffres. Un contrôle de longueur seul
     * l'accepterait — et le client découvrirait au premier appel que son
     * numéro ne mène nulle part.
     */
    public function test_nine_digits_are_not_enough_for_senegal(): void
    {
        $this->assertNull(IndicatifsPays::normaliser('SN', '123456789'));
        $this->assertNull(IndicatifsPays::normaliser('SN', '791234567'));
    }

    // =======================================================================
    // LES AUTRES PAYS
    // =======================================================================

    public function test_other_countries_use_their_own_length(): void
    {
        $this->assertSame('+2250701020304', IndicatifsPays::normaliser('CI', '07 01 02 03 04'));
        $this->assertSame('+33612345678', IndicatifsPays::normaliser('FR', '0612345678'));
        $this->assertSame('+22370010203', IndicatifsPays::normaliser('ML', '70 01 02 03'));
    }

    /** Une longueur qui n'existe pas dans le pays est refusée. */
    public function test_a_wrong_length_is_refused(): void
    {
        $this->assertNull(IndicatifsPays::normaliser('FR', '12345'));
        $this->assertNull(IndicatifsPays::normaliser('ML', '7001020304050'));
    }

    /** L'indicatif ressaisi par le client n'est pas doublé. */
    public function test_a_retyped_dialling_code_is_not_doubled(): void
    {
        $this->assertSame('+33612345678', IndicatifsPays::normaliser('FR', '+33 6 12 34 56 78'));
        $this->assertSame('+2250701020304', IndicatifsPays::normaliser('CI', '225 07 01 02 03 04'));
    }

    public function test_an_unknown_country_is_refused(): void
    {
        $this->assertNull(IndicatifsPays::normaliser('ZZ', '773831364'));
    }

    // =======================================================================
    // LE CATALOGUE
    // =======================================================================

    /**
     * L'AFRIQUE DE L'OUEST EN TÊTE, ET LE SÉNÉGAL EN PREMIER.
     *
     * Une liste alphabétique mondiale mettrait le Sénégal en cent-quatrième
     * position : sur un produit dont la quasi-totalité des clients est
     * sénégalaise, le champ deviendrait une corvée de défilement.
     */
    public function test_senegal_comes_first(): void
    {
        $codes = array_keys(IndicatifsPays::catalogue());

        $this->assertSame('SN', $codes[0]);
        $this->assertSame(['SN', 'CI', 'ML', 'BF'], array_slice($codes, 0, 4));
    }

    /** Chaque entrée porte un drapeau, un nom et au moins une longueur. */
    public function test_every_entry_is_complete(): void
    {
        foreach (IndicatifsPays::catalogue() as $code => [$nom, $indicatif, $drapeau, $longueurs]) {
            $this->assertNotEmpty($nom, "Nom manquant pour {$code}.");
            $this->assertStringStartsWith('+', $indicatif, "Indicatif mal formé pour {$code}.");
            $this->assertNotEmpty($drapeau, "Drapeau manquant pour {$code}.");
            $this->assertNotEmpty($longueurs, "Aucune longueur déclarée pour {$code}.");
        }
    }

    // =======================================================================
    // LE FORMULAIRE
    // =======================================================================

    /** Le sélecteur est présent à l'inscription, Sénégal présélectionné. */
    public function test_the_registration_form_offers_the_selector(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertStringContainsString('name="phone_pays"', $html);
        $this->assertStringContainsString('value="SN" selected', $html);
        $this->assertStringContainsString('+225', $html, 'La Côte d’Ivoire devrait être proposée.');
    }

    /**
     * SANS PAYS ENVOYÉ, LE SÉNÉGAL S'APPLIQUE.
     *
     * Le champ est facultatif : un ancien formulaire, un lien profond ou une
     * intégration existante continuent de fonctionner sans rien changer.
     */
    public function test_an_absent_country_falls_back_to_senegal(): void
    {
        $this->assertSame('+221773831364', IndicatifsPays::normaliser(null, '773831364'));
    }
}
