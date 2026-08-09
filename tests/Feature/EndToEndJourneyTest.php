<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\Profile;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Support\EnforcedCsrfToken;
use Tests\TestCase;

/**
 * PARCOURS COMPLET, de la landing à la création du profil.
 *
 * Vérifie les redirections, l'absence de 419 (jeton CSRF), et la séparation
 * stricte entre le COMPTE et le PROFIL.
 */
class EndToEndJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        $this->seed(TemplateSeeder::class);
    }

    public function test_full_journey_from_landing_to_profile_creation(): void
    {
        Mail::fake();

        // --- 1. Landing accessible, sans erreur -----------------------------
        $this->get('/')->assertOk()->assertSee('Créer un compte', false);

        // --- 2. Formulaire d'inscription ------------------------------------
        $this->get('/register')->assertOk();

        // Le jeton d'idempotence est bien posé en session par le contrôleur.
        $token = Str::uuid()->toString();

        $response = $this->withSession(['registration.idem' => $token])
            ->post('/register', [
                'name' => 'Awa Ndiaye',
                'email' => 'awa@exemple.sn',
                'phone' => '77 383 13 64',
                'password' => 'motdepasse-solide-123',
                'password_confirmation' => 'motdepasse-solide-123',
                '_idem' => $token,
            ]);

        // Aucune 419 : la réponse est une redirection, pas une erreur.
        $response->assertStatus(302)->assertRedirect(route('registration.pending'));

        // Le COMPTE n'existe pas encore : seule la demande en attente est créée.
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_registrations', 1);

        // --- 3. Page de confirmation ----------------------------------------
        $this->withSession(['registration.pending_email' => 'awa@exemple.sn'])
            ->get(route('registration.pending'))
            ->assertOk();

        // --- 4. Clic sur le lien reçu : le compte est créé -------------------
        $raw = Str::random(64);
        PendingRegistration::first()->forceFill([
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addHour(),
        ])->save();

        $this->get(route('registration.confirm', ['token' => $raw]))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
        $user = User::firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        // L'essai gratuit a été ouvert automatiquement.
        $this->assertTrue($user->fresh()->hasActiveSubscription());
        $this->assertTrue($user->fresh()->isOnTrial());

        // --- 5. Dashboard vide : compte sans profil -------------------------
        $this->assertNull($user->profile);

        $this->get('/dashboard')->assertOk()->assertSee('Créer mon profil', false);

        // --- 6. Déconnexion puis reconnexion, SANS 419 ----------------------
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();

        $this->get('/login')->assertOk();

        $login = $this->post('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ]);

        $login->assertStatus(302);           // jamais 419
        $login->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // --- 7. Accès au parcours de création du profil ---------------------
        $this->get(route('profile.create.step1'))->assertOk();

        // Étape 1
        $this->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante en gestion',
        ])->assertRedirect(route('profile.create.step2'));

        // Étape 2
        $this->post(route('profile.store.step2'), [
            'phone' => '77 383 13 64',
        ])->assertRedirect(route('profile.create.step3'));

        // Étape 3 → persistance et aperçu
        $this->post(route('profile.store.step3'), [
            'template_id' => Template::first()->id,
            'primary_color' => '#0B3B2E',
        ])->assertRedirect(route('profile.preview'));

        // --- 8. Le PROFIL existe, inactif tant qu'il n'est pas activé -------
        $this->assertDatabaseCount('profiles', 1);
        $profile = Profile::firstOrFail();
        $this->assertSame($user->id, $profile->user_id);
        $this->assertFalse($profile->is_active);
        $this->assertSame('+221773831364', $profile->phone);

        $this->get(route('profile.preview'))->assertOk();
    }

    /** Un saut d'étape renvoie sur la première étape non franchie. */
    public function test_wizard_steps_cannot_be_skipped(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.create.step3'))
            ->assertRedirect(route('profile.create.step1'));
    }

    /** Une session expirée sur le login ne mène jamais à une impasse. */
    public function test_expired_csrf_on_login_redirects_instead_of_showing_419(): void
    {
        // Sans cette substitution, le contrôle CSRF se neutralise sous PHPUnit :
        // la requête passait normalement et le test ne prouvait rien.
        // Voir Tests\Support\EnforcedCsrfToken.
        $this->app->bind(ValidateCsrfToken::class, EnforcedCsrfToken::class);

        $response = $this->post('/login', [
            'email' => 'inconnu@exemple.sn',
            'password' => 'peu-importe',
            '_token' => 'jeton-invalide',
        ]);

        // Retour sur le formulaire (302), jamais une page 419 brute.
        $response->assertRedirect(route('login'));
    }
}
