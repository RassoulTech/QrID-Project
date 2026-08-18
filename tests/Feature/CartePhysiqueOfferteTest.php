<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\User;
use App\Services\Payment\CheckoutService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LA CARTE PVC EST OFFERTE UNE SEULE FOIS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CES TESTS COMPTENT PLUS QUE LA PLUPART
 * ═══════════════════════════════════════════════════════════════════════
 * Chaque défaut ici se paie en argent réel, pas en pixels : une carte PVC
 * imprimée, mise sous pli et expédiée. Un essai gratuit qui déclencherait une
 * carte, ou un renouvellement qui en déclencherait une seconde, coûte à chaque
 * occurrence — et personne ne s'en aperçoit avant la facture de l'imprimeur.
 *
 * Les tests valent donc surtout par ce qu'ils INTERDISENT.
 */
class CartePhysiqueOfferteTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->client = User::factory()->create(['email_verified_at' => now()]);
        Profile::factory()->for($this->client)->create(['is_active' => false]);
    }

    private function encaisser(string $planSlug): Payment
    {
        $plan = Plan::where('slug', $planSlug)->firstOrFail();
        $checkout = app(CheckoutService::class);

        $paiement = $checkout->start($this->client, $plan, 'wave');
        $checkout->redirectUrl($paiement);   // FakeGateway pose provider_ref

        $checkout->succeed($paiement->fresh(), [
            'statut' => 'succes',
            'reference' => $paiement->fresh()->provider_ref,
        ]);

        return $paiement->fresh();
    }

    // =======================================================================
    // CE QUI DOIT ARRIVER
    // =======================================================================

    /** Le premier paiement encaissé ouvre le droit à la carte. */
    public function test_the_first_paid_checkout_grants_the_card(): void
    {
        $this->assertFalse($this->client->hasPhysicalCard());

        $this->encaisser('standard');

        $this->assertTrue($this->client->fresh()->hasPhysicalCard());
    }

    // =======================================================================
    // CE QUI NE DOIT JAMAIS ARRIVER
    // =======================================================================

    /**
     * UN ESSAI GRATUIT N'OUVRE AUCUN DROIT.
     *
     * Sans cette garde, chaque inscription commanderait une carte physique à
     * imprimer et à expédier pour quelqu'un qui n'a rien payé.
     */
    public function test_a_free_trial_grants_nothing(): void
    {
        $this->encaisser('essai-gratuit');

        $this->assertFalse($this->client->fresh()->hasPhysicalCard());
    }

    /**
     * UN RENOUVELLEMENT NE DONNE PAS UNE SECONDE CARTE.
     *
     * Le client s'abonne à un service, il n'achète pas un support. La date du
     * premier octroi ne bouge donc plus.
     */
    public function test_a_renewal_never_grants_a_second_card(): void
    {
        $this->encaisser('standard');
        $premiere = $this->client->fresh()->physical_card_granted_at;

        $this->travel(1)->days();
        $this->encaisser('standard');

        $this->assertEquals(
            $premiere->timestamp,
            $this->client->fresh()->physical_card_granted_at->timestamp,
            'Un renouvellement a rouvert le droit à une carte.'
        );
    }

    /** Un paiement en échec n'ouvre aucun droit. */
    public function test_a_failed_payment_grants_nothing(): void
    {
        $plan = Plan::where('slug', 'standard')->firstOrFail();
        $checkout = app(CheckoutService::class);

        $paiement = $checkout->start($this->client, $plan, 'wave');
        $checkout->fail($paiement, 'refus opérateur');

        $this->assertFalse($this->client->fresh()->hasPhysicalCard());
    }

    // =======================================================================
    // LE CATALOGUE
    // =======================================================================

    /** Le tarif est unique : une seule formule payante en vente. */
    public function test_only_one_paid_plan_is_on_sale(): void
    {
        $payantes = Plan::active()->where('price_fcfa', '>', 0)->get();

        $this->assertCount(1, $payantes);
        $this->assertSame('standard', $payantes->first()->slug);
        $this->assertSame(3500, $payantes->first()->price_fcfa);
        $this->assertSame(90, $payantes->first()->duration_days);
    }

    /**
     * LES ANCIENNES FORMULES SURVIVENT, RETIRÉES DE LA VENTE.
     *
     * Les supprimer casserait les abonnements en cours qui pointent sur leur
     * ligne, et priverait l'historique des paiements du nom de ce qui a été
     * acheté — une pièce comptable ne se réécrit pas.
     */
    public function test_the_old_plans_are_withdrawn_not_deleted(): void
    {
        /*
         | ON REJOUE LA VRAIE MIGRATION.
         |
         | Sur une base neuve, « mensuel » et « annuel » n'ont jamais existé :
         | il n'y a rien à retirer, et un test qui les exigerait échouerait sur
         | une installation propre. Le cas qui compte est celui d'une base
         | EXISTANTE — c'est la production.
         */
        $ancien = Plan::create([
            'name' => 'Mensuel',
            'slug' => 'mensuel',
            'price_fcfa' => 2500,
            'duration_days' => 30,
            'features' => [],
            'is_active' => true,
        ]);

        $this->seed(PlanSeeder::class);

        $ancien->refresh();

        $this->assertNotNull(
            Plan::where('slug', 'mensuel')->first(),
            'La formule a été supprimée : les abonnements en cours pointent dessus.'
        );

        $this->assertFalse((bool) $ancien->is_active, 'La formule est encore en vente.');

        // Et son prix n'a pas bougé : une pièce comptable ne se réécrit pas.
        $this->assertSame(2500, $ancien->price_fcfa);
    }

    /** L'essai dure bien quatorze jours. */
    public function test_the_trial_lasts_fourteen_days(): void
    {
        $this->assertSame(14, Plan::where('slug', 'essai-gratuit')->value('duration_days'));
    }
}
