<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Profile;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QUAND AUCUNE PASSERELLE NE PEUT ENCAISSER.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS RÉPARENT
 * ═══════════════════════════════════════════════════════════════════════
 * En production, un clic sur « Payer » rendait une page 500 :
 *
 *     « Une erreur est survenue. Le problème vient de nous, pas de vous.
 *       Notre équipe en a été informée. »
 *
 * Trois mensonges en trois lignes. Rien n'était en panne — FakeGateway est
 * simplement interdite hors développement. Personne n'était informé. Et
 * c'était le seul écran du produit où le client sortait son argent.
 *
 * Constaté en production les 12 et 17 août : deux POST /abonnement/paiement,
 * deux 500.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE DÉFAUT INVISIBLE QUI SE CACHAIT DERRIÈRE
 * ═══════════════════════════════════════════════════════════════════════
 * start() écrit le Payment « pending » AVANT que initiate() ne lève. Chaque
 * clic laissait donc un paiement fantôme en base — et ce sont eux qui
 * alimentent l'alerte « en attente depuis plus d'une heure » du récapitulatif
 * du soir. L'absence de contrat opérateur se serait signalée, tous les soirs,
 * comme une panne d'encaissement.
 */
class GatewayUnavailableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        Profile::factory()->create(['user_id' => $this->user->id, 'is_active' => false]);
    }

    /**
     * Une passerelle branchée mais incapable d'encaisser ici.
     *
     * C'est exactement l'état de la production : l'implémentation existe, son
     * garde-fou refuse de tourner. On ne teste pas APP_ENV, on teste la
     * RÉPONSE de la passerelle — c'est elle que le produit interroge, et le
     * jour où une vraie passerelle attendra ses clés, elle répondra pareil.
     */
    private function sansPasserelle(): void
    {
        $this->app->instance(PaymentGateway::class, new class implements PaymentGateway
        {
            public function initiate(Payment $payment): string
            {
                throw new \RuntimeException('Aucun contrat opérateur.');
            }

            public function confirms(Payment $payment, array $callback): bool
            {
                return false;
            }

            public function name(): string
            {
                return 'aucune';
            }

            public function estDisponible(): bool
            {
                return false;
            }
        });
    }

    // =======================================================================
    // L'ÉCRAN
    // =======================================================================

    /** L'écran s'ouvre normalement — il ne tombe pas, il explique. */
    public function test_the_payment_screen_still_opens(): void
    {
        $this->sansPasserelle();

        $this->actingAs($this->user)
            ->get(route('abonnement.paiement'))
            ->assertOk()
            // PAS de `false` en second argument : Blade échappe l'apostrophe
            // en &#039;, donc chercher la forme brute ne trouve jamais rien.
            // Avec l'échappement par défaut, l'attendu subit la même
            // transformation que la page, et les deux se comparent enfin.
            ->assertSee('Paiement à la main, pour l\'instant');
    }

    /**
     * PLUS DE BOUTON QUI NE PEUT PAS ABOUTIR.
     *
     * Proposer « Payer et publier ma carte » quand rien ne peut encaisser,
     * c'est fabriquer la déception à retardement.
     */
    public function test_it_no_longer_offers_a_button_that_cannot_work(): void
    {
        $this->sansPasserelle();

        $this->actingAs($this->user)
            ->get(route('abonnement.paiement'))
            ->assertDontSee('Payer et publier ma carte', false);
    }

    /**
     * La sortie de secours est donnée, pas seulement le constat d'échec.
     *
     * On vise le LIBELLÉ DU BOUTON, pas le mot « WhatsApp » : le gabarit
     * porte déjà une bulle flottante « Une question ? » qui contient ce mot,
     * et l'assertion passerait même si mon panneau avait disparu.
     */
    public function test_it_offers_the_manual_route(): void
    {
        $this->sansPasserelle();

        $this->actingAs($this->user)
            ->get(route('abonnement.paiement'))
            ->assertOk()
            ->assertSee('Écrire sur WhatsApp', false);
    }

    /**
     * L'EXPLOITANT COINCÉ SUR SON PROPRE ÉCRAN.
     *
     * Un administrateur qui bute ici a déjà le pouvoir de se débloquer : la
     * prolongation d'abonnement, motif obligatoire et journalisée. Il lui
     * manquait le chemin — trois écrans plus loin, dans une liste où il ne
     * pensait pas se chercher.
     */
    public function test_an_admin_is_shown_the_way_to_the_extension(): void
    {
        $this->sansPasserelle();

        $this->user->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($this->user)
            ->get(route('abonnement.paiement'))
            ->assertOk()
            ->assertSee(route('admin.clients.show', $this->user), false);
    }

    /** Un client ordinaire ne voit évidemment rien de tout cela. */
    public function test_an_ordinary_client_is_shown_no_admin_shortcut(): void
    {
        $this->sansPasserelle();

        $this->actingAs($this->user)
            ->get(route('abonnement.paiement'))
            ->assertOk()
            ->assertDontSee('prolonger cet abonnement', false);
    }

    // =======================================================================
    // LA SOUMISSION
    // =======================================================================

    /** Le formulaire forcé ne rend plus 500. */
    public function test_posting_anyway_does_not_return_a_server_error(): void
    {
        $this->sansPasserelle();

        $this->actingAs($this->user)
            ->post(route('abonnement.paiement.store'), ['plan' => 'standard', 'method' => 'wave'])
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    /**
     * LE TEST QUI COMPTE VRAIMENT — aucun paiement fantôme.
     *
     * Sans la garde posée AVANT start(), chaque tentative laissait un Payment
     * « pending » que personne ne confirmerait jamais, et le récapitulatif du
     * soir aurait fini par crier à la panne d'encaissement.
     */
    public function test_it_writes_no_ghost_payment(): void
    {
        $this->sansPasserelle();

        $this->actingAs($this->user)
            ->post(route('abonnement.paiement.store'), ['plan' => 'standard', 'method' => 'wave']);

        $this->assertSame(0, Payment::count());
    }

    /** Et rien n'est accordé au passage : ni abonnement, ni carte publiée. */
    public function test_nothing_is_granted(): void
    {
        $this->sansPasserelle();

        $this->actingAs($this->user)
            ->post(route('abonnement.paiement.store'), ['plan' => 'standard', 'method' => 'wave']);

        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $this->assertFalse((bool) $this->user->fresh()->profile->is_active);
    }

    // =======================================================================
    // NON-RÉGRESSION — la passerelle disponible n'a pas changé de comportement
    // =======================================================================

    /** Avec FakeGateway, qui EST disponible en test, le parcours reste entier. */
    public function test_an_available_gateway_still_opens_a_payment(): void
    {
        $this->actingAs($this->user)
            ->post(route('abonnement.paiement.store'), ['plan' => 'standard', 'method' => 'wave'])
            ->assertRedirect();

        $this->assertSame(1, Payment::count());
        $this->assertSame(Payment::STATUS_PENDING, Payment::firstOrFail()->status);
    }

    /** Et l'écran propose bien le bouton. */
    public function test_an_available_gateway_still_shows_the_button(): void
    {
        $this->actingAs($this->user)
            ->get(route('abonnement.paiement'))
            ->assertOk()
            ->assertSee('Payer et publier ma carte', false);
    }
}
