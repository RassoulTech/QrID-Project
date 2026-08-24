<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PAIEMENT — de la formule choisie à la carte publiée.
 *
 * Le parcours est déroulé en entier avec FakeGateway : aucun appel réseau,
 * mais toutes les issues réelles sont exercées — encaissement, refus, abandon.
 * Ne tester que le succès reviendrait à ne rien tester du tout : ce sont les
 * deux autres qui font perdre des clients.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email_verified_at' => now()]);

        $this->profile = Profile::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);
    }

    private function payer(string $plan = 'standard', string $method = 'wave')
    {
        return $this->actingAs($this->user)
            ->post(route('abonnement.paiement.store'), ['plan' => $plan, 'method' => $method]);
    }

    // =======================================================================
    // OUVERTURE
    // =======================================================================

    public function test_a_pending_payment_is_written_before_leaving_for_the_operator(): void
    {
        $this->payer()->assertRedirect();

        $payment = Payment::firstOrFail();

        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame('wave', $payment->method);
        $this->assertNotNull($payment->provider_ref, 'La référence opérateur doit être posée.');

        // Aucun droit accordé à ce stade.
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $this->assertFalse($this->profile->fresh()->is_active);
    }

    /** Le montant vient de la formule en base, JAMAIS du formulaire. */
    public function test_the_amount_comes_from_the_plan_not_from_the_form(): void
    {
        $prix = Plan::where('slug', 'standard')->value('price_fcfa');

        $this->actingAs($this->user)->post(route('abonnement.paiement.store'), [
            'plan' => 'standard',
            'method' => 'wave',
            'amount_fcfa' => 1,     // tentative de fixer son prix
        ]);

        $this->assertSame($prix, Payment::firstOrFail()->amount_fcfa);
    }

    public function test_the_free_trial_plan_cannot_be_bought(): void
    {
        $this->payer(plan: 'essai-gratuit')->assertSessionHasErrors('plan');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_unknown_payment_method_is_refused(): void
    {
        $this->payer(method: 'especes')->assertSessionHasErrors('method');

        $this->assertDatabaseCount('payments', 0);
    }

    // =======================================================================
    // ISSUES
    // =======================================================================

    public function test_a_confirmed_payment_opens_the_subscription_and_publishes_the_card(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $this->actingAs($this->user)
            ->get(route('abonnement.retour', [
                'payment' => $payment->id,
                'statut' => 'succes',
                'reference' => $payment->provider_ref,
            ]))
            ->assertRedirect(route('abonnement.confirmation'));

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $this->assertTrue($this->profile->fresh()->is_active);

        // Le paiement est rattaché à l'abonnement qu'il a financé.
        $this->assertNotNull($payment->fresh()->subscription_id);
    }

    /**
     * L'écran de confirmation remet au client ce qu'il vient d'acheter.
     *
     * Renvoyer sec au tableau de bord laissait le moment le plus important du
     * parcours sans accusé de réception : ni carte, ni lien, ni fichiers.
     */
    public function test_the_confirmation_screen_hands_over_the_card_and_its_files(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $this->actingAs($this->user)->get(route('abonnement.retour', [
            'payment' => $payment->id,
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]));

        $html = $this->actingAs($this->user)
            ->get(route('abonnement.confirmation'))
            ->assertOk()
            ->getContent();

        // La carte est désormais x-card-duo : voir CardPresentationTest.
        $this->assertStringContainsString('class="card ', $html);
        $this->assertStringContainsString(route('profile.public', $this->profile->slug), $html);
        $this->assertStringContainsString(route('carte.qr.png'), $html);
        $this->assertStringContainsString(route('carte.imprimable'), $html);
    }

    /** On n'atteint pas la confirmation sans avoir réellement payé. */
    public function test_the_confirmation_screen_is_out_of_reach_without_a_live_card(): void
    {
        $this->actingAs($this->user)
            ->get(route('abonnement.confirmation'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_a_refused_payment_grants_nothing_and_keeps_the_card_intact(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $this->actingAs($this->user)
            ->get(route('abonnement.retour', ['payment' => $payment->id, 'statut' => 'echec']))
            ->assertRedirect(route('abonnement.paiement'))
            ->assertSessionHas('error');

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $this->assertFalse($this->profile->fresh()->is_active);
    }

    public function test_a_cancelled_payment_says_so_plainly_and_loses_nothing(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $this->actingAs($this->user)
            ->get(route('abonnement.retour', ['payment' => $payment->id, 'statut' => 'annule']))
            ->assertRedirect(route('abonnement.paiement'))
            ->assertSessionHas('warning');

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertDatabaseHas('profiles', ['id' => $this->profile->id]);
    }

    /**
     * Une référence inventée n'encaisse rien.
     * Sans ce contrôle, l'URL de retour suffirait à s'offrir un abonnement.
     */
    public function test_a_forged_reference_never_confirms_a_payment(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $this->actingAs($this->user)->get(route('abonnement.retour', [
            'payment' => $payment->id,
            'statut' => 'succes',
            'reference' => 'REFERENCE-INVENTEE',
        ]))->assertSessionHas('error');

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
    }

    /** Le paiement d'autrui ne s'active pas depuis son propre compte. */
    public function test_a_payment_belonging_to_someone_else_is_forbidden(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $intrus = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($intrus)->get(route('abonnement.retour', [
            'payment' => $payment->id,
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]))->assertForbidden();
    }

    /** Un retour rejoué ne double ni l'abonnement ni la durée. */
    public function test_replaying_the_callback_changes_nothing(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $retour = route('abonnement.retour', [
            'payment' => $payment->id,
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]);

        $this->actingAs($this->user)->get($retour);
        $fin = $this->user->fresh()->activeSubscription()->ends_at;

        $this->actingAs($this->user)->get($retour);

        $this->assertSame(1, Subscription::where('user_id', $this->user->id)->count());
        $this->assertEquals($fin, $this->user->fresh()->activeSubscription()->ends_at);
    }

    // =======================================================================
    // RENOUVELLEMENT
    // =======================================================================

    /** Un renouvellement anticipé s'AJOUTE au temps restant, il ne l'efface pas. */
    public function test_an_early_renewal_extends_instead_of_restarting(): void
    {
        $fin = now()->addDays(10);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::where('slug', 'standard')->value('id'),
            'starts_at' => now()->subDays(20),
            'ends_at' => $fin,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->payer();
        $payment = Payment::firstOrFail();

        $this->actingAs($this->user)->get(route('abonnement.retour', [
            'payment' => $payment->id,
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]));

        $duree = Plan::where('slug', 'standard')->value('duration_days');

        $this->assertTrue(
            $this->user->fresh()->activeSubscription()->ends_at->greaterThan($fin->copy()->addDays($duree - 1)),
            'Le temps restant doit être conservé et prolongé.'
        );
    }

    /** L'essai gratuit est REMPLACÉ, pas prolongé : on ne paie pas du temps offert. */
    public function test_a_free_trial_is_replaced_not_extended(): void
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::where('slug', 'essai-gratuit')->value('id'),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(14),
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->payer();
        $payment = Payment::firstOrFail();

        $this->actingAs($this->user)->get(route('abonnement.retour', [
            'payment' => $payment->id,
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]));

        $abonnement = $this->user->fresh()->activeSubscription();

        $this->assertSame('standard', $abonnement->plan->slug);
        $this->assertSame(
            1,
            Subscription::where('user_id', $this->user->id)->where('status', Subscription::STATUS_ACTIVE)->count(),
            'Un seul abonnement actif à la fois.'
        );
    }

    // =======================================================================
    // SIMULATION
    // =======================================================================

    public function test_the_simulation_screen_offers_the_three_real_outcomes(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $html = $this->actingAs($this->user)
            ->get(route('abonnement.simulation', $payment))
            ->assertOk()
            ->getContent();

        foreach (['statut=succes', 'statut=echec', 'statut=annule'] as $issue) {
            $this->assertStringContainsString($issue, $html, "Issue manquante : {$issue}");
        }
    }

    public function test_the_simulation_screen_is_not_reachable_by_another_account(): void
    {
        $this->payer();
        $payment = Payment::firstOrFail();

        $intrus = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($intrus)->get(route('abonnement.simulation', $payment))->assertForbidden();
    }
}
