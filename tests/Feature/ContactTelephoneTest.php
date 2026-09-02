<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * LE TÉLÉPHONE DU FORMULAIRE DE CONTACT.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════════
 * Ce champ valait `['nullable','string','max:30']` : n'importe quelle suite
 * de trente caractères passait. C'était le SEUL formulaire du produit à ne
 * pas utiliser la règle partagée — l'inscription, l'adresse de livraison et
 * l'étape 2 de la carte l'appliquent toutes les trois.
 *
 * La conséquence n'était pas théorique. Un visiteur laissait un numéro
 * tronqué, le formulaire l'acceptait sans rien dire, et l'on découvrait à
 * l'appel qu'il ne menait nulle part. Personne ne pouvait plus le joindre —
 * et lui pensait avoir laissé ses coordonnées.
 *
 * Le champ reste FACULTATIF. C'est le contrôle qui a changé, pas l'exigence :
 * imposer un numéro ferait renoncer ceux qui ne veulent pas être appelés,
 * pour une information dont on n'a pas besoin pour répondre.
 */
class ContactTelephoneTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, string> $champs */
    private function envoyer(array $champs = [])
    {
        Mail::fake();

        return $this->post(route('contact.store'), array_merge([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.test',
            'subject' => 'information',
            'message' => 'Bonjour, je souhaite en savoir plus sur vos cartes.',
            'site_web' => '',
        ], $champs));
    }

    /** Sans numéro : le message passe. C'est le cas le plus courant. */
    public function test_the_form_still_works_without_a_phone_number(): void
    {
        $this->envoyer()->assertSessionHasNoErrors();

        $this->assertNotNull(ContactMessage::first());
    }

    /**
     * LE CŒUR : un numéro invalide est refusé au lieu d'être accepté en
     * silence.
     */
    public function test_an_unreachable_number_is_refused(): void
    {
        $this->envoyer([
            'phone_pays' => 'SN',
            'phone' => '123',
        ])->assertSessionHasErrors('phone');

        $this->assertNull(ContactMessage::first(),
            'Le message a été enregistré avec un numéro que personne ne peut rappeler.');
    }

    /**
     * Un préfixe qui n'est attribué à aucun opérateur sénégalais est refusé.
     * Un simple contrôle de longueur laisserait passer ces neuf chiffres.
     */
    public function test_a_number_with_an_unassigned_prefix_is_refused(): void
    {
        $this->envoyer([
            'phone_pays' => 'SN',
            'phone' => '123456789',
        ])->assertSessionHasErrors('phone');
    }

    /**
     * UN NUMÉRO VALIDE EST STOCKÉ AU FORMAT INTERNATIONAL.
     *
     * Le visiteur saisit sa graphie habituelle ; la base n'en connaît
     * qu'une seule. Sans cette normalisation, la table contiendrait autant
     * de formes que de visiteurs et aucune recherche ne les retrouverait.
     */
    public function test_a_valid_number_is_stored_in_international_form(): void
    {
        $this->envoyer([
            'phone_pays' => 'SN',
            'phone' => '77 383 13 64',
        ])->assertSessionHasNoErrors();

        $this->assertSame('+221773831364', ContactMessage::first()->phone);
    }

    /**
     * ET LE PAYS EST RÉELLEMENT PRIS EN COMPTE.
     *
     * C'est tout l'intérêt de la règle partagée : le même numéro n'a pas la
     * même validité selon le pays choisi. Sans ce lien, un numéro ivoirien
     * serait jugé contre les règles sénégalaises.
     */
    public function test_the_chosen_country_decides_what_is_valid(): void
    {
        $this->envoyer([
            'phone_pays' => 'CI',
            'phone' => '0102030405',
        ])->assertSessionHasNoErrors();

        $this->assertStringStartsWith('+225', (string) ContactMessage::first()->phone);
    }
}
