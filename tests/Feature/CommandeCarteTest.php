<?php

namespace Tests\Feature;

use App\Models\CardOrder;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\User;
use App\Services\Payment\CheckoutService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LA CHAÎNE DE COMMANDE DES CARTES PHYSIQUES.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CHAQUE DÉFAUT ICI SE PAIE EN OBJETS RÉELS
 * ═══════════════════════════════════════════════════════════════════════
 * Une carte imprimée pour un essai gratuit, une seconde carte à un
 * renouvellement, un lot envoyé sans adresse : chacun coûte du PVC, de
 * l'encre et un envoi. Et personne ne s'en aperçoit avant la facture.
 *
 * Les tests valent donc surtout par ce qu'ils INTERDISENT.
 */
class CommandeCarteTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->client = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'Awa Ndiaye',
            'phone' => '+221773831364',
        ]);

        Profile::factory()->for($this->client)->create(['is_active' => false]);
    }

    private function encaisser(string $planSlug = 'standard'): void
    {
        $plan = Plan::where('slug', $planSlug)->firstOrFail();
        $checkout = app(CheckoutService::class);

        $paiement = $checkout->start($this->client, $plan, 'wave');
        $checkout->redirectUrl($paiement);

        $checkout->succeed($paiement->fresh(), [
            'statut' => 'succes',
            'reference' => $paiement->fresh()->provider_ref,
        ]);
    }

    // =======================================================================
    // LA NAISSANCE DE LA COMMANDE
    // =======================================================================

    /**
     * LA COMMANDE NAÎT AVEC LE DROIT, ET SANS ADRESSE.
     *
     * L'inverse — attendre l'adresse pour créer la commande — perdrait la
     * trace de tous ceux qui paient puis ferment l'onglet : ils auraient payé
     * une carte que rien ne réclamerait.
     */
    public function test_a_paid_checkout_opens_a_card_order(): void
    {
        $this->encaisser();

        $commande = CardOrder::where('user_id', $this->client->id)->first();

        $this->assertNotNull($commande);
        $this->assertSame(CardOrder::STATUS_PENDING, $commande->status);
        $this->assertSame('Awa Ndiaye', $commande->recipient_name);
        $this->assertNull($commande->address_line, 'L\'adresse ne peut pas être connue à ce stade.');
    }

    /** Un essai gratuit ne commande AUCUNE carte. */
    public function test_a_free_trial_orders_nothing(): void
    {
        $this->encaisser('essai-gratuit');

        $this->assertSame(0, CardOrder::count());
    }

    /** Un renouvellement ne commande pas une seconde carte. */
    public function test_a_renewal_orders_no_second_card(): void
    {
        $this->encaisser();
        $this->travel(1)->days();
        $this->encaisser();

        $this->assertSame(1, CardOrder::count());
    }

    // =======================================================================
    // L'ADRESSE, CÔTÉ CLIENT
    // =======================================================================

    public function test_the_client_can_fill_the_delivery_address(): void
    {
        $this->encaisser();

        $this->actingAs($this->client)
            ->patch(route('carte.physique.update'), [
                'recipient_name' => 'Awa Ndiaye',
                'phone_pays' => 'SN',
                'phone' => '77 383 13 64',
                'address_line' => 'Cité Keur Gorgui, villa 42',
                'city' => 'Dakar',
                'region' => 'Dakar',
            ])
            ->assertRedirect(route('dashboard'));

        $commande = CardOrder::firstOrFail();

        $this->assertSame('Dakar', $commande->city);
        $this->assertSame('+221773831364', $commande->phone, 'Le numéro doit être normalisé.');
        $this->assertTrue($commande->adresseComplete());
    }

    /**
     * L'ADRESSE SE FIGE DÈS QUE LA CARTE PART.
     *
     * Laisser modifier après coup ferait croire au client que le colis suivra,
     * alors qu'il est déjà adressé ailleurs. La garde est SERVEUR : un onglet
     * resté ouvert contournerait l'écran.
     */
    public function test_the_address_freezes_once_in_production(): void
    {
        $this->encaisser();

        CardOrder::firstOrFail()->update([
            'status' => CardOrder::STATUS_IN_BATCH,
            'batch_id' => 'LOT-TEST',
            'address_line' => 'Adresse initiale',
            'city' => 'Thiès',
        ]);

        $this->actingAs($this->client)
            ->patch(route('carte.physique.update'), [
                'recipient_name' => 'Quelqu\'un d\'autre',
                'phone_pays' => 'SN',
                'phone' => '77 383 13 64',
                'address_line' => 'Adresse changée',
                'city' => 'Dakar',
            ])
            ->assertSessionHas('warning');

        $this->assertSame('Adresse initiale', CardOrder::firstOrFail()->address_line);
    }

    // =======================================================================
    // LA PRODUCTION, CÔTÉ ADMINISTRATION
    // =======================================================================

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * UNE COMMANDE SANS ADRESSE NE PART JAMAIS EN PRODUCTION.
     *
     * Elle serait fabriquée, payée, et resterait dans un carton faute de
     * savoir où l'envoyer.
     */
    public function test_an_order_without_an_address_never_enters_a_batch(): void
    {
        $sansAdresse = CardOrder::factory()->sansAdresse()->create();
        $complete = CardOrder::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.cards.batch'), [
                'commandes' => [$sansAdresse->id, $complete->id],
            ])
            ->assertRedirect();

        $this->assertSame(CardOrder::STATUS_PENDING, $sansAdresse->fresh()->status);
        $this->assertSame(CardOrder::STATUS_IN_BATCH, $complete->fresh()->status);
        $this->assertNull($sansAdresse->fresh()->batch_id);
        $this->assertNotNull($complete->fresh()->batch_id);
    }

    /** Un lot avance d'un bloc, et chaque passage pose SA date. */
    public function test_a_batch_advances_as_one_and_stamps_its_date(): void
    {
        $commandes = CardOrder::factory()->count(3)->create([
            'status' => CardOrder::STATUS_IN_BATCH,
            'batch_id' => 'LOT-20260818-AAAA',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.cards.advance'), [
                'batch_id' => 'LOT-20260818-AAAA',
                'statut' => CardOrder::STATUS_SHIPPED,
            ])
            ->assertRedirect();

        foreach ($commandes as $commande) {
            $frais = $commande->fresh();

            $this->assertSame(CardOrder::STATUS_SHIPPED, $frais->status);
            $this->assertNotNull($frais->shipped_at, 'La date d\'expédition n\'a pas été posée.');
        }
    }

    /** L'écran de production s'ouvre et compte ce qui attend. */
    public function test_the_production_screen_counts_what_is_waiting(): void
    {
        CardOrder::factory()->count(2)->create();
        CardOrder::factory()->sansAdresse()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.cards.index'))
            ->assertOk()
            ->assertSee('en attente de production', false)
            ->assertSee('Adresse manquante', false);
    }

    /** L'export imprimeur sort bien un CSV. */
    public function test_the_printer_export_streams_a_csv(): void
    {
        CardOrder::factory()->create();

        $reponse = $this->actingAs($this->admin())
            ->get(route('admin.cards.export'))
            ->assertOk();

        $this->assertStringContainsString('text/csv', $reponse->headers->get('Content-Type'));
    }
}
