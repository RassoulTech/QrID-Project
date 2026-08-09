<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileDraft;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use App\Services\ProfileWizardService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MATRICE DE L'ESPACE CLIENT — le contrat de navigation du produit.
 *
 * Une ligne de la matrice = un test. Si l'un tombe, une porte s'est ouverte ou
 * fermée quelque part sans qu'on l'ait décidé.
 */
class ClientAreaMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    private function utilisateur(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    /** Un compte, sa carte, et un abonnement dans l'état demandé. */
    private function avecCarte(bool $active = true, bool $expire = false): array
    {
        $user = $this->utilisateur();

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'is_active' => $active,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => Plan::where('slug', 'mensuel')->value('id'),
            'starts_at' => now()->subDays(40),
            'ends_at' => $expire ? now()->subDay() : now()->addDays(20),
            'status' => $expire ? Subscription::STATUS_EXPIRED : Subscription::STATUS_ACTIVE,
        ]);

        return [$user, $profile];
    }

    // =======================================================================
    // SANS CARTE
    // =======================================================================

    public function test_without_a_profile_the_dashboard_shows_the_empty_state(): void
    {
        $this->actingAs($this->utilisateur())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Créer mon profil');
    }

    public function test_without_a_profile_steps_two_and_three_send_back_to_the_first_gap(): void
    {
        $user = $this->utilisateur();

        $this->actingAs($user)->get(route('profile.create.step2'))
            ->assertRedirect(route('profile.create.step1'));

        $this->actingAs($user)->get(route('profile.create.step3'))
            ->assertRedirect(route('profile.create.step1'));
    }

    public function test_without_a_profile_the_preview_sends_back_to_step_one(): void
    {
        $this->actingAs($this->utilisateur())
            ->get(route('profile.preview'))
            ->assertRedirect(route('profile.create.step1'));
    }

    public function test_without_a_profile_the_payment_sends_back_to_step_one(): void
    {
        $this->actingAs($this->utilisateur())
            ->get(route('abonnement.paiement'))
            ->assertRedirect(route('profile.create.step1'));
    }

    // =======================================================================
    // PARCOURS EN COURS
    // =======================================================================

    /**
     * L'AVANCEMENT SURVIT À LA DÉCONNEXION.
     *
     * La déconnexion détruit la session — c'est la parade contre la fixation
     * de session, elle ne bouge pas. Sans la table profile_drafts, toute la
     * saisie était perdue et l'utilisateur repartait de zéro.
     */
    public function test_wizard_progress_survives_a_logout_and_a_new_login(): void
    {
        $user = User::factory()->create([
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante',
        ])->assertRedirect(route('profile.create.step2'));

        $this->assertDatabaseHas('profile_drafts', ['user_id' => $user->id, 'next_step' => 2]);

        // Déconnexion : la session est détruite.
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ])->assertRedirect(route('dashboard'));

        // L'étape 2 reste atteignable, et l'étape 1 a gardé sa saisie.
        $this->get(route('profile.create.step2'))->assertOk();
        $this->get(route('profile.create.step1'))->assertOk()->assertSee('Awa', false);
    }

    public function test_the_dashboard_offers_to_resume_an_unfinished_wizard(): void
    {
        $user = $this->utilisateur();

        $this->actingAs($user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante',
        ]);

        $this->get(route('dashboard'))->assertOk();

        $this->assertSame(2, ProfileDraft::where('user_id', $user->id)->value('next_step'));
    }

    /** « Recommencer » efface la mémoire durable, pas seulement la session. */
    public function test_starting_over_forgets_the_stored_draft(): void
    {
        $user = $this->utilisateur();

        $this->actingAs($user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante',
        ]);

        $this->assertDatabaseCount('profile_drafts', 1);

        app(ProfileWizardService::class)->clear();

        $this->assertDatabaseCount('profile_drafts', 0);
    }

    // =======================================================================
    // CARTE CRÉÉE, PAS ENCORE PUBLIÉE
    // =======================================================================

    public function test_an_unpublished_profile_may_reach_the_preview_and_the_payment(): void
    {
        [$user] = $this->avecCarte(active: false);

        $this->actingAs($user)->get(route('profile.preview'))->assertOk();
        $this->actingAs($user)->get(route('abonnement.paiement'))->assertOk();
    }

    // =======================================================================
    // CARTE ACTIVE, ABONNEMENT VALIDE
    // =======================================================================

    public function test_an_active_profile_is_sent_from_the_wizard_to_editing(): void
    {
        [$user] = $this->avecCarte();

        $this->actingAs($user)->get(route('profile.create.step1'))
            ->assertRedirect(route('profile.edit'));
    }

    /** Le paiement parle alors de RENOUVELLEMENT, pas de première souscription. */
    public function test_an_active_subscription_turns_the_payment_into_a_renewal(): void
    {
        [$user] = $this->avecCarte();

        $this->actingAs($user)->get(route('abonnement.paiement'))
            ->assertOk()
            ->assertSee('Renouveler', false)
            ->assertDontSee('Payer et publier ma carte', false);
    }

    // =======================================================================
    // ABONNEMENT EXPIRÉ
    // =======================================================================

    public function test_an_expired_subscription_takes_the_public_card_offline(): void
    {
        [$user, $profile] = $this->avecCarte(active: true, expire: true);

        $this->get(route('profile.public', $profile->slug))->assertNotFound();

        // Et le tableau de bord reste accessible, il ne tombe pas avec l'abonnement.
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_an_expired_subscription_still_allows_paying_again(): void
    {
        [$user] = $this->avecCarte(active: true, expire: true);

        $this->actingAs($user)->get(route('abonnement.paiement'))
            ->assertOk()
            ->assertSee('Payer et publier ma carte', false);
    }

    // =======================================================================
    // MENU
    // =======================================================================

    /** L'entrée « Mon profil » reste active pendant TOUT le parcours. */
    public function test_the_active_menu_entry_holds_through_the_whole_wizard(): void
    {
        $user = $this->utilisateur();

        $this->actingAs($user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa', 'last_name' => 'Ndiaye', 'job_title' => 'Consultante',
        ]);

        foreach (['profile.create.step1', 'profile.create.step2'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            /*
             | Le motif ne suppose PAS l'ordre des attributs : depuis la fusion
             | des deux coques, `href` précède `class` dans le partial. Un motif
             | qui l'imposait a échoué sur un simple déplacement d'attribut,
             | alors que le comportement testé n'avait pas bougé d'un pouce.
             */
            $this->assertMatchesRegularExpression(
                '/<a[^>]*href="[^"]*profil[^"]*"[^>]*class="[^"]*adm-nav__lien is-active/',
                $html,
                "L'entrée « Mon profil » devrait être active sur {$route}."
            );
        }
    }

    /** Aucun lien mort dans l'espace client. */
    public function test_no_dead_links_in_the_client_area(): void
    {
        [$user] = $this->avecCarte(active: false);

        Template::first();   // le parcours en a besoin

        foreach (['dashboard', 'profile.preview', 'abonnement.paiement', 'compte.edit'] as $route) {
            $this->assertStringNotContainsString(
                'href="#"',
                $this->actingAs($user)->get(route($route))->getContent(),
                "Lien mort sur {$route}."
            );
        }
    }
}
