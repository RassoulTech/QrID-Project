<?php

namespace Tests\Feature\Auth;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * MATRICE DES REDIRECTIONS — le contrat d'authentification du produit.
 *
 * Chaque ligne de la matrice est un test. Si l'un tombe, une porte s'est
 * ouverte ou fermée quelque part sans qu'on l'ait décidé.
 */
class AuthenticationMatrixTest extends TestCase
{
    use RefreshDatabase;

    // =======================================================================
    // CONNEXION
    // =======================================================================

    public function test_a_valid_account_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /** Le message doit être traduit, jamais la clé brute « auth.failed ». */
    public function test_a_wrong_password_shows_a_translated_message(): void
    {
        User::factory()->create(['email' => 'awa@exemple.sn', 'email_verified_at' => now()]);

        $reponse = $this->from('/login')->post('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'ce-n-est-pas-le-bon',
        ]);

        $reponse->assertSessionHasErrors('email');

        $message = session('errors')->first('email');

        $this->assertSame('E-mail ou mot de passe incorrect.', $message);

        /*
         | Le message ne doit AFFIRMER l'absence d'aucun compte : il s'affiche
         | aussi quand le compte existe et que seul le mot de passe est faux.
         | L'ancienne formulation a réellement conduit à supprimer puis recréer
         | des comptes valides.
         */
        $this->assertStringNotContainsString('aucun compte', $message);
        $this->assertStringNotContainsString('auth.', $message, 'Une clé brute s\'affiche à l\'écran.');
        $this->assertGuest();
    }

    public function test_a_blocked_account_is_refused_and_its_session_destroyed(): void
    {
        User::factory()->create([
            'email' => 'suspendu@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
            'is_blocked' => true,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'suspendu@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame(
            'Ce compte est suspendu. Contactez le support pour le réactiver.',
            session('errors')->first('email')
        );

        // Aucune session ne subsiste, même si le mot de passe était bon.
        $this->assertGuest();
    }

    /** Suspendu EN COURS de navigation : éjecté à la requête suivante. */
    public function test_an_account_blocked_mid_session_is_ejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['is_blocked' => true])->saveQuietly();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // =======================================================================
    // VISITEUR NON CONNECTÉ
    // =======================================================================

    #[DataProvider('routesProtegees')]
    public function test_a_guest_is_sent_to_login(string $route): void
    {
        $this->get($route)->assertRedirect(route('login'));
    }

    public static function routesProtegees(): array
    {
        return [
            '/dashboard' => ['/dashboard'],
            '/admin' => ['/admin'],
            'aperçu du profil' => ['/profil/apercu'],
            'parcours de création' => ['/profil/creation/etape-1'],
            'compte' => ['/compte'],
        ];
    }

    public function test_a_guest_sees_login_and_register(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    // =======================================================================
    // CONNECTÉ, VÉRIFIÉ, SANS PROFIL
    // =======================================================================

    public function test_without_a_profile_the_dashboard_shows_the_empty_state(): void
    {
        $this->actingAs($this->utilisateur())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Créer mon profil');
    }

    public function test_without_a_profile_the_preview_sends_back_to_step_one(): void
    {
        $this->actingAs($this->utilisateur())
            ->get(route('profile.preview'))
            ->assertRedirect(route('profile.create.step1'));
    }

    public function test_an_authenticated_user_cannot_reach_login_or_register(): void
    {
        $user = $this->utilisateur();

        $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/register')->assertRedirect(route('dashboard'));
    }

    // =======================================================================
    // CONNECTÉ, VÉRIFIÉ, AVEC PROFIL
    // =======================================================================

    public function test_with_a_profile_the_dashboard_shows_the_active_state(): void
    {
        [$user] = $this->utilisateurAvecProfil();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mon QR Code');
    }

    /**
     * Posséder un profil ne change RIEN à la porte d'entrée : login et
     * register renvoient sur le tableau de bord, comme sans profil.
     */
    public function test_with_a_profile_login_and_register_still_lead_to_the_dashboard(): void
    {
        [$user] = $this->utilisateurAvecProfil();

        $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/register')->assertRedirect(route('dashboard'));
    }

    /** Le parcours de création bascule sur l'édition, sans boucler. */
    public function test_with_a_profile_the_wizard_switches_to_editing(): void
    {
        [$user] = $this->utilisateurAvecProfil();

        $this->actingAs($user)
            ->get(route('profile.create.step1'))
            ->assertRedirect(route('profile.edit'));

        // L'édition recharge la session puis revient sur l'étape 1, qui s'affiche.
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('profile.create.step1'));

        $this->actingAs($user)->get(route('profile.create.step1'))->assertOk();
    }

    // =======================================================================
    // ADMINISTRATEUR
    // =======================================================================

    public function test_an_admin_lands_on_the_admin_area_after_logging_in(): void
    {
        User::factory()->create([
            'email' => 'admin@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->post('/login', [
            'email' => 'admin@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ])->assertRedirect(route('admin.home'));
    }

    /** TRANCHÉ : un administrateur garde l'accès au tableau de bord client. */
    public function test_an_admin_may_still_use_the_client_dashboard(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_a_regular_user_is_refused_from_the_admin_area(): void
    {
        $this->actingAs($this->utilisateur())->get('/admin')->assertForbidden();
    }

    public function test_an_authenticated_admin_is_redirected_from_login_to_admin(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get('/login')->assertRedirect(route('admin.home'));
    }

    // =======================================================================
    // PAGE DEMANDÉE AVANT INTERCEPTION
    // =======================================================================

    public function test_login_returns_the_user_to_the_page_they_asked_for(): void
    {
        User::factory()->create([
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
        ]);

        // Interception : /compte est mémorisé comme destination voulue.
        $this->get('/compte')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ])->assertRedirect('/compte');
    }

    // =======================================================================
    // DÉCONNEXION
    // =======================================================================

    public function test_logging_out_invalidates_the_session_and_returns_home(): void
    {
        $user = $this->utilisateur();

        $this->actingAs($user);
        $idAvant = session()->getId();
        $jetonAvant = session()->token();

        $this->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNotSame($idAvant, session()->getId(), 'L\'identifiant de session doit changer.');

        /*
         | Le jeton CSRF doit être renouvelé, et pas seulement la session.
         | Sans cela, le formulaire de connexion servi juste après repart avec
         | un jeton périmé : la première tentative de reconnexion tombe en 419.
         */
        $this->assertNotSame($jetonAvant, session()->token(), 'Le jeton CSRF doit être régénéré.');
    }

    // =======================================================================
    // COMPTE NON VÉRIFIÉ — ne doit pas pouvoir exister
    // =======================================================================

    /**
     * L'inscription ne crée un compte QU'APRÈS confirmation, et pose
     * email_verified_at dans la même transaction. Aucun chemin ne produit
     * un compte non vérifié.
     */
    /**
     * Si l'état impossible survient malgré tout, aucune page blanche : la
     * session est fermée et l'utilisateur revient sur la connexion.
     *
     * Le « verified » du framework aurait cherché route('verification.notice'),
     * absente ici, et renvoyé une 500.
     */
    public function test_an_unverified_account_is_logged_out_not_crashed(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_registration_never_produces_an_unverified_account(): void
    {
        // Le formulaire porte un jeton d'idempotence (anti-double-soumission).
        // Sans lui, RegisteredUserController renvoie sur /register sans rien
        // écrire : le test passait à côté de tout ce qu'il prétend vérifier.
        $idem = 'jeton-idempotence-test';

        $this->withSession(['registration.idem' => $idem])->post('/register', [
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '77 383 13 64',
            'password' => 'motdepasse-solide-123',
            'password_confirmation' => 'motdepasse-solide-123',
            '_idem' => $idem,
        ]);

        // Rien en users : seulement une inscription en attente.
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'awa@exemple.sn']);

        // Et aucun compte de la base n'est dépourvu de vérification.
        $this->assertSame(0, User::whereNull('email_verified_at')->count());
    }

    // =======================================================================
    // MOT DE PASSE — hachage
    // =======================================================================

    public function test_a_password_is_always_stored_as_a_bcrypt_hash(): void
    {
        $user = User::factory()->create(['password' => 'motdepasse-solide-123']);

        $hache = $user->fresh()->getAuthPassword();

        $this->assertMatchesRegularExpression('/^\$2[aby]\$\d{2}\$/', $hache);
        $this->assertSame(60, strlen($hache));
        $this->assertTrue(Hash::check('motdepasse-solide-123', $hache));
        $this->assertFalse(Hash::check(Hash::make('motdepasse-solide-123'), $hache), 'Double hachage détecté.');
    }

    // -----------------------------------------------------------------------

    private function utilisateur(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    /** @return array{0:User, 1:Profile} */
    private function utilisateurAvecProfil(): array
    {
        $this->seed(TemplateSeeder::class);

        $user = $this->utilisateur();

        $profile = Profile::factory()->create(['user_id' => $user->id, 'is_active' => true]);

        Subscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => Plan::factory()->create()->id,
        ]);

        return [$user, $profile];
    }
}
