<?php

namespace Tests\Feature\Auth;

use App\Mail\WelcomeMail;
use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as UtilisateurSocialite;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * CONNEXION PAR GOOGLE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS PROTÈGENT AVANT TOUT
 * ═══════════════════════════════════════════════════════════════════════
 * Une prise de contrôle de compte.
 *
 * Retrouver un compte par son ADRESSE au retour de Google est commode et
 * dangereux : si l'adresse rendue n'était pas vérifiée, n'importe qui
 * pourrait créer un compte Google portant l'adresse d'autrui et ouvrir le
 * compte QrID correspondant.
 *
 * Le contrôle qui l'empêche tient en une ligne de code — et c'est
 * précisément le genre de ligne qu'un remaniement supprime sans que rien ne
 * casse visiblement. D'où ces tests.
 */
class GoogleSignInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'identifiant-de-test',
            'services.google.client_secret' => 'secret-de-test',
        ]);

        // L'essai gratuit s'ouvre à la création du compte.
        Plan::factory()->create([
            'slug' => 'essai-gratuit',
            'name' => 'Essai gratuit',
            'duration_days' => 15,
            'price_fcfa' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Simule le retour de Google.
     *
     * `$verifiee` reproduit le drapeau `email_verified` de la charge utile.
     * C'est le pivot de toute la sécurité de ce parcours : il doit pouvoir
     * être mis à false dans un test, sans quoi le contrôle ne serait jamais
     * éprouvé.
     */
    private function googleRepond(
        string $email = 'awa@gmail.com',
        string $id = 'google-123456',
        ?string $nom = 'Awa Ndiaye',
        bool $verifiee = true,
    ): void {
        $utilisateur = (new UtilisateurSocialite)->setRaw([
            'sub' => $id,
            'email' => $email,
            'email_verified' => $verifiee,
            'name' => $nom,
        ]);

        $utilisateur->id = $id;
        $utilisateur->email = $email;
        $utilisateur->name = $nom;
        $utilisateur->avatar = 'https://exemple.test/photo.jpg';

        $fournisseur = Mockery::mock(GoogleProvider::class);
        $fournisseur->shouldReceive('user')->andReturn($utilisateur);

        Socialite::shouldReceive('driver')->with('google')->andReturn($fournisseur);
    }

    private function retour()
    {
        return $this->get(route('auth.google.callback'));
    }

    // =======================================================================
    // LE CAS NOMINAL
    // =======================================================================

    public function test_a_new_visitor_gets_an_account_and_reaches_the_dashboard(): void
    {
        Mail::fake();
        $this->googleRepond();

        $this->retour()->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'awa@gmail.com',
            'google_id' => 'google-123456',
        ]);
    }

    /**
     * L'ADRESSE EST DÉJÀ VÉRIFIÉE : aucun e-mail de confirmation.
     *
     * C'est le gain principal de ce parcours. Google a vérifié l'adresse ;
     * la revérifier nous-mêmes ferait attendre l'utilisateur devant sa boîte
     * pour une information qu'on possède déjà.
     */
    public function test_the_account_is_verified_without_any_confirmation_email(): void
    {
        Mail::fake();
        $this->googleRepond();

        $this->retour();

        $this->assertNotNull(User::first()->email_verified_at);
        $this->assertDatabaseCount('pending_registrations', 0);
    }

    /** L'essai gratuit s'ouvre, et la bienvenue part — comme par le formulaire. */
    public function test_a_new_account_opens_its_trial_and_receives_the_welcome(): void
    {
        Mail::fake();
        $this->googleRepond();

        $this->retour();

        $this->assertNotNull(
            User::first()->activeSubscription(),
            'Un compte créé par Google doit ouvrir son essai comme les autres.'
        );

        Mail::assertSent(WelcomeMail::class);
    }

    /**
     * AUCUN MOT DE PASSE N'EST INVENTÉ.
     *
     * Un mot de passe aléatoire existerait sans que personne ne le connaisse :
     * le compte paraîtrait protégé par un secret que son propriétaire ne peut
     * ni utiliser ni changer. NULL dit la vérité.
     */
    public function test_no_password_is_ever_invented(): void
    {
        Mail::fake();
        $this->googleRepond();

        $this->retour();

        $user = User::first();

        $this->assertNull($user->password);
        $this->assertFalse($user->hasPassword());
        $this->assertTrue($user->usesGoogle());
    }

    // =======================================================================
    // LA SÉCURITÉ — le test le plus important du fichier
    // =======================================================================

    /**
     * UNE ADRESSE NON VÉRIFIÉE PAR GOOGLE EST REFUSÉE, SANS EXCEPTION.
     *
     * Sans ce contrôle, créer un compte Google avec l'adresse d'autrui — sans
     * jamais la vérifier — donnerait accès au compte QrID de cette personne.
     * C'est une prise de contrôle complète, en trois minutes, sans outil.
     */
    public function test_an_unverified_google_address_is_refused(): void
    {
        $this->googleRepond(verifiee: false);

        $this->retour()->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    /** Et elle ne prend surtout pas le contrôle d'un compte existant. */
    public function test_an_unverified_address_never_seizes_an_existing_account(): void
    {
        $victime = User::factory()->create([
            'email' => 'awa@gmail.com',
            'password' => Hash::make('motdepasse-solide-2026'),
            'email_verified_at' => now(),
        ]);

        $this->googleRepond(email: 'awa@gmail.com', verifiee: false);

        $this->retour();

        $this->assertGuest();
        $this->assertNull($victime->refresh()->google_id);
    }

    /** Un compte suspendu ne s'ouvre pas davantage par Google. */
    public function test_a_blocked_account_is_refused(): void
    {
        User::factory()->create([
            'email' => 'awa@gmail.com',
            'google_id' => 'google-123456',
            'is_blocked' => true,
        ]);

        $this->googleRepond();

        $this->retour()
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => trans('auth.blocked')]);

        $this->assertGuest();
    }

    // =======================================================================
    // LE RATTACHEMENT
    // =======================================================================

    /**
     * QUELQU'UN INSCRIT PAR MOT DE PASSE PEUT ARRIVER PAR GOOGLE.
     *
     * Lui refuser l'entrée serait incompréhensible : c'est son adresse, et
     * Google vient de le confirmer. Le compte est donc rattaché, et non
     * dupliqué — deux comptes pour une personne seraient bien pires.
     */
    public function test_an_existing_password_account_is_linked_not_duplicated(): void
    {
        Mail::fake();

        $existant = User::factory()->create([
            'email' => 'awa@gmail.com',
            'password' => Hash::make('motdepasse-solide-2026'),
            'email_verified_at' => now(),
        ]);

        $this->googleRepond(email: 'awa@gmail.com');

        $this->retour()->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('google-123456', $existant->refresh()->google_id);

        // Le mot de passe existant n'est pas effacé : les deux chemins
        // fonctionnent désormais pour ce compte.
        $this->assertTrue($existant->hasPassword());
    }

    /**
     * UN RATTACHEMENT N'EST PAS UNE INSCRIPTION.
     *
     * Souhaiter la bienvenue à quelqu'un qui a un compte depuis six mois, et
     * lui rouvrir un essai gratuit, serait faux deux fois.
     */
    public function test_linking_an_existing_account_sends_no_welcome_and_opens_no_trial(): void
    {
        Mail::fake();

        $existant = User::factory()->create([
            'email' => 'awa@gmail.com',
            'password' => Hash::make('motdepasse-solide-2026'),
            'email_verified_at' => now(),
        ]);

        $this->googleRepond(email: 'awa@gmail.com');

        $this->retour();

        Mail::assertNotSent(WelcomeMail::class);
        $this->assertNull($existant->refresh()->activeSubscription());
    }

    /** Un retour ultérieur reconnaît le compte par son identifiant Google. */
    public function test_a_returning_user_is_recognised_by_their_google_id(): void
    {
        Mail::fake();

        // Première venue.
        $this->googleRepond();
        $this->retour();
        $this->post(route('logout'));

        // Seconde venue : même identifiant, adresse changée entre-temps.
        Mockery::close();
        $this->googleRepond(email: 'awa.nouvelle@gmail.com', id: 'google-123456');
        $this->retour()->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
    }

    /** Une demande d'inscription en attente devient caduque. */
    public function test_a_pending_registration_on_the_same_address_is_dropped(): void
    {
        Mail::fake();

        PendingRegistration::create([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@gmail.com',
            'phone' => '+221773831364',
            'password' => Hash::make('motdepasse-solide-2026'),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => Carbon::now()->addHour(),
            'resend_count' => 0,
            'last_sent_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);

        $this->googleRepond();
        $this->retour();

        $this->assertDatabaseCount('pending_registrations', 0);
    }

    // =======================================================================
    // LES RETOURS QUI SE PASSENT MAL
    // =======================================================================

    /**
     * L'UTILISATEUR A REFUSÉ : ce n'est pas une erreur, c'est une décision.
     *
     * Elle se respecte en silence. Afficher « la connexion a échoué » à
     * quelqu'un qui vient d'appuyer sur « Annuler » lui apprend qu'on n'a pas
     * compris ce qu'il faisait.
     */
    public function test_refusing_consent_returns_quietly_to_the_login_screen(): void
    {
        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertGuest();
    }

    /**
     * UNE PANNE CHEZ GOOGLE NE DOIT JAMAIS RENDRE UNE ERREUR 500.
     *
     * Ce point d'entrée est appelé par un tiers, avec des paramètres qu'on ne
     * maîtrise pas. Session perdue entre l'aller et le retour, réseau coupé,
     * jeton d'état invalide : chacun de ces cas est NORMAL et doit se terminer
     * par un message en français, pas par une page d'erreur.
     */
    public function test_a_failure_at_google_never_produces_a_500(): void
    {
        $fournisseur = Mockery::mock(GoogleProvider::class);
        $fournisseur->shouldReceive('user')->andThrow(new RuntimeException('état invalide'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($fournisseur);

        $this->retour()
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** Sans clés configurées, la route ne mène nulle part de dangereux. */
    public function test_the_route_is_inert_without_keys(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('auth.google'))->assertRedirect(route('login'));
        $this->retour()->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * Un compte Google sans nom ne produit pas « Bonjour , ».
     *
     * Certains comptes n'exposent pas de nom. Le repli sur la partie locale de
     * l'adresse donne un mot imparfait mais lisible, ce qu'aucune chaîne vide
     * ne fait.
     */
    public function test_an_account_without_a_name_still_gets_one(): void
    {
        Mail::fake();
        $this->googleRepond(nom: null);

        $this->retour();

        $this->assertNotSame('', trim((string) User::first()->name));
    }

    // =======================================================================
    // APRÈS — le compte Google doit pouvoir vivre sa vie
    // =======================================================================

    /**
     * UN COMPTE GOOGLE PEUT SE DONNER UN MOT DE PASSE.
     *
     * C'est le chemin par lequel il acquiert un SECOND moyen d'accès. Lui
     * réclamer son « mot de passe actuel » serait une impasse : il n'en a pas,
     * la règle ne peut jamais passer, et l'écran refuserait indéfiniment sans
     * expliquer pourquoi.
     */
    public function test_a_google_account_can_set_a_password_without_giving_a_current_one(): void
    {
        Mail::fake();
        $this->googleRepond();
        $this->retour();

        $user = User::first();
        $this->assertFalse($user->hasPassword());

        $this->actingAs($user)->put(route('password.update'), [
            'password' => 'mon-nouveau-mot-de-passe-2026',
            'password_confirmation' => 'mon-nouveau-mot-de-passe-2026',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($user->refresh()->hasPassword());

        // Et Google continue de fonctionner : on ajoute un accès, on n'en
        // remplace pas un.
        $this->assertTrue($user->usesGoogle());
    }

    /**
     * Un compte AVEC mot de passe doit toujours donner l'actuel.
     *
     * Sans cette vérification, l'assouplissement précédent ouvrirait une porte
     * sur tous les comptes : quiconque emprunterait une session ouverte
     * pourrait changer le mot de passe sans connaître l'ancien.
     */
    public function test_an_account_with_a_password_must_still_provide_it(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('motdepasse-solide-2026'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->put(route('password.update'), [
            'password' => 'tentative-sans-ancien-2026',
            'password_confirmation' => 'tentative-sans-ancien-2026',
        ])->assertSessionHasErrors('current_password', errorBag: 'updatePassword');

        $this->assertTrue(Hash::check('motdepasse-solide-2026', $user->refresh()->password));
    }

    /**
     * « Mot de passe oublié » reste ouvert à un compte Google.
     *
     * C'est son filet de secours : le jour où il perd l'accès à sa boîte
     * Google, ou préfère simplement un mot de passe, le parcours habituel
     * doit fonctionner sans traitement particulier.
     */
    public function test_the_password_reset_remains_available_to_a_google_account(): void
    {
        Mail::fake();
        $this->googleRepond();
        $this->retour();
        $this->post(route('logout'));

        $this->post(route('password.email'), ['email' => 'awa@gmail.com'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'awa@gmail.com']);
    }

    // =======================================================================
    // L'ADMINISTRATION
    // =======================================================================

    /** Un administrateur arrive dans SON espace, pas sur le tableau de bord client. */
    public function test_an_admin_lands_in_the_admin_area(): void
    {
        User::factory()->create([
            'email' => 'admin@gmail.com',
            'google_id' => 'google-admin',
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->googleRepond(email: 'admin@gmail.com', id: 'google-admin');

        $this->retour()->assertRedirect(route('admin.overview'));
    }
}
