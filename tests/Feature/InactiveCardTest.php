<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LA CARTE QUI N'EST PAS ENCORE EN LIGNE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CETTE PAGE RÉPARE
 * ═══════════════════════════════════════════════════════════════════════
 * Le premier geste d'un client qui vient de créer sa carte est de scanner son
 * propre QR Code pour voir si « ça marche ». Il tombait sur une page d'erreur
 * nue. Rien ne lui disait que son QR Code était juste, que rien n'était cassé,
 * et qu'il ne restait qu'une étape — activer.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE STATUT RESTE 404, ET C'EST LE POINT DÉLICAT
 * ═══════════════════════════════════════════════════════════════════════
 * Répondre 200 ici distinguerait une carte inactive d'un slug inexistant : on
 * pourrait énumérer les comptes en essayant des adresses. Le corps devient
 * utile, le code de réponse ne dit rien de plus qu'avant. Plusieurs tests
 * ci-dessous existent uniquement pour empêcher qu'on « corrige » ce 404.
 */
/*
 | NOTE SUR assertSee ET L'ÉCHAPPEMENT.
 |
 | Les phrases de cet écran passent par __(), donc par {{ }}, qui échappe
 | l'apostrophe en « &#039; ». Les assertions qui les cherchent ont perdu leur
 | second argument « false » : sans lui, Laravel échappe la valeur attendue de
 | la même façon, et la comparaison redevient juste.
 |
 | Le rendu à l'écran n'a pas bougé d'un pixel — seuls les octets ont changé.
 | Celles qui gardent « false » cherchent des URL, qui ne s'échappent pas.
 */
class InactiveCardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::factory()->create(['slug' => 'standard', 'price_fcfa' => 2500, 'duration_days' => 30]);

        $this->user = User::factory()->create();

        $this->profile = Profile::factory()->for($this->user)->create([
            'slug' => 'awa-ndiaye',
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'is_active' => false,
        ]);
    }

    private function abonnementActif(): void
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::first()->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);
    }

    // =======================================================================
    // LE PROPRIÉTAIRE, QUI VIENT DE SCANNER SA PROPRE CARTE
    // =======================================================================

    /**
     * LE CAS DE LOIN LE PLUS FRÉQUENT : tout est prêt, il reste à activer.
     *
     * Le message doit dire que le QR Code est juste. C'est la seule chose que
     * le client se demande à cet instant : « est-ce que j'ai raté quelque
     * chose, ou est-ce que ça ne marche pas ? »
     */
    public function test_the_owner_is_told_the_card_only_needs_publishing(): void
    {
        $this->abonnementActif();

        $reponse = $this->actingAs($this->user)
            ->get(route('profile.public', 'awa-ndiaye'))
            ->assertNotFound();

        $reponse->assertSee('Votre carte n\'est pas encore en ligne');
        $reponse->assertSee('votre QR Code est juste', false);
        $reponse->assertSee(route('profile.preview'), false);
    }

    /** Sans abonnement actif, le message nomme le vrai obstacle et mène au paiement. */
    public function test_an_expired_subscription_points_to_the_payment_page(): void
    {
        $this->profile->update(['is_active' => true]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::first()->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDay(),
        ]);

        $reponse = $this->actingAs($this->user)
            ->get(route('profile.public', 'awa-ndiaye'))
            ->assertNotFound();

        $reponse->assertSee('abonnement n\'est plus actif');
        $reponse->assertSee(route('abonnement.paiement'), false);
    }

    /**
     * Le lien et le QR Code ne changent pas à la réactivation.
     *
     * C'est ce que le client veut savoir avant de payer : ses cartes déjà
     * distribuées vont-elles remarcher ? Le dire explicitement lève le doute
     * qui ferait renoncer.
     */
    public function test_it_promises_the_link_and_qr_survive_a_reactivation(): void
    {
        $this->profile->update(['is_active' => true]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::first()->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user)
            ->get(route('profile.public', 'awa-ndiaye'))
            ->assertSee('ni le lien ni le QR Code ne changent', false);
    }

    /**
     * L'EXPLOITANT VA DROIT À LA PROLONGATION.
     *
     * Tant qu'aucune passerelle n'encaisse, « Activer ma carte » menait au
     * paiement, qui ne menait nulle part. Le propriétaire administrateur
     * tournait entre les deux écrans sans jamais atteindre la seule voie
     * ouverte — dont il a pourtant les droits.
     */
    public function test_an_owner_who_is_admin_goes_straight_to_the_extension(): void
    {
        $this->profile->update(['is_active' => true]);
        $this->user->forceFill(['role' => User::ROLE_ADMIN])->save();

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::first()->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDay(),
        ]);

        $reponse = $this->actingAs($this->user)
            ->get(route('profile.public', 'awa-ndiaye'))
            ->assertNotFound();

        $reponse->assertSee(route('admin.clients.show', $this->user), false);
        $reponse->assertSee('Prolonger mon abonnement', false);
    }

    /** Un propriétaire ordinaire garde le chemin normal, vers le paiement. */
    public function test_an_ordinary_owner_still_goes_to_the_payment_screen(): void
    {
        $this->profile->update(['is_active' => true]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::first()->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user)
            ->get(route('profile.public', 'awa-ndiaye'))
            ->assertSee(route('abonnement.paiement'), false)
            ->assertSee('Activer ma carte', false);
    }

    /**
     * UNE CARTE SUSPENDUE N'ENVOIE PAS PAYER.
     *
     * La suspension vient de l'administration : l'argent n'y changerait rien.
     * Proposer le paiement ferait payer pour un blocage que le paiement ne
     * lève pas — c'est la faute la plus coûteuse que cette page pourrait
     * commettre.
     */
    public function test_a_suspended_card_never_offers_to_pay(): void
    {
        $this->abonnementActif();

        // forceFill, exactement comme ProfileDeactivationService : ces colonnes
        // ne sont pas dans $fillable, et un update() les ignorerait en silence.
        $this->profile->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_reason' => 'Contenu signalé',
        ])->save();

        $reponse = $this->actingAs($this->user)
            ->get(route('profile.public', 'awa-ndiaye'))
            ->assertNotFound();

        $reponse->assertSee('suspendue', false);
        $reponse->assertDontSee(route('abonnement.paiement'), false);
    }

    // =======================================================================
    // TOUT AUTRE VISITEUR — LA PAGE NE DOIT RIEN RÉVÉLER
    // =======================================================================

    /**
     * Un inconnu n'apprend NI le nom, NI que l'adresse a été réservée.
     *
     * Sans cette règle, la page deviendrait un annuaire des comptes non
     * publiés : il suffirait d'essayer des prénoms.
     */
    public function test_a_stranger_learns_nothing_about_the_owner(): void
    {
        $this->abonnementActif();

        $reponse = $this->get(route('profile.public', 'awa-ndiaye'))->assertNotFound();

        $reponse->assertDontSee('Awa Ndiaye');
        $reponse->assertDontSee('Ndiaye');
        $reponse->assertSee('Cette carte n\'est pas active');
    }

    /**
     * Un slug inexistant et une carte inactive doivent être INDISCERNABLES
     * par le code de réponse. C'est toute la raison du 404.
     */
    public function test_an_unknown_slug_and_an_inactive_card_answer_alike(): void
    {
        $this->abonnementActif();

        $inactive = $this->get(route('profile.public', 'awa-ndiaye'));
        $inconnue = $this->get(route('profile.public', 'personne-de-ce-nom'));

        $this->assertSame(404, $inactive->getStatusCode());
        $this->assertSame(404, $inconnue->getStatusCode());
    }

    /** La fiche vCard reste fermée, elle aussi : pas de seconde porte. */
    public function test_the_vcard_stays_closed_while_the_card_is_inactive(): void
    {
        $this->abonnementActif();

        $this->actingAs($this->user)
            ->get(route('profile.vcard', 'awa-ndiaye'))
            ->assertNotFound();
    }

    // =======================================================================
    // NON-RÉGRESSION
    // =======================================================================

    /** Une carte active continue d'afficher le profil, et non ce message. */
    public function test_an_active_card_still_shows_the_profile(): void
    {
        $this->abonnementActif();
        $this->profile->update(['is_active' => true]);

        $this->get(route('profile.public', 'awa-ndiaye'))
            ->assertOk()
            ->assertSee('Awa Ndiaye')
            ->assertDontSee('Cette carte n\'est pas active');
    }
}
