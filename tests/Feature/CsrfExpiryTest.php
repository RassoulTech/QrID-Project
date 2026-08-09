<?php

namespace Tests\Feature;

use App\Models\ProfileDraft;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\EnforcedCsrfToken;
use Tests\TestCase;

/**
 * Jeton CSRF expiré : ce qui ne doit JAMAIS arriver.
 *
 * Un 419 est une page restée ouverte, pas une faute. La règle tenue ici :
 * l'utilisateur revient sur son formulaire, ses saisies sont encore là, et
 * la requête n'a pas été exécutée.
 */
class CsrfExpiryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ValidateCsrfToken se neutralise lui-même sous PHPUnit. On lui substitue
     * une version qui vérifie réellement le jeton : sans cela, TOUS les tests
     * de cette classe passaient à travers le contrôle sans rien vérifier.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ValidateCsrfToken::class, EnforcedCsrfToken::class);
    }

    /** Poste sans jeton valide : exactement ce que fait une page trop vieille. */
    private function postExpire(string $url, array $data = [])
    {
        return $this->post($url, $data + ['_token' => 'jeton-perime']);
    }

    // -----------------------------------------------------------------------
    // Parcours de création — le cas critique
    // -----------------------------------------------------------------------

    public function test_expiry_on_wizard_step_one_keeps_every_field(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postExpire(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante',
            'company' => 'Teranga Conseil',
        ])
            ->assertRedirect(route('profile.create.step1'))
            ->assertSessionHas('warning')
            ->assertSessionHasInput('first_name', 'Awa')
            ->assertSessionHasInput('last_name', 'Ndiaye')
            ->assertSessionHasInput('job_title', 'Consultante')
            ->assertSessionHasInput('company', 'Teranga Conseil');

        // Rien n'a été écrit : la requête a bien été refusée.
        $this->assertDatabaseCount('profiles', 0);
    }

    /** Le retour se fait sur l'étape RÉELLEMENT en cours, pas sur la première. */
    public function test_expiry_returns_to_the_step_actually_in_progress(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['profile_wizard' => ['completed' => [1]]]);

        $this->postExpire(route('profile.store.step2'), [
            'phone' => '77 383 13 64',
            'public_email' => 'awa@exemple.sn',
        ])
            ->assertRedirect(route('profile.create.step2'))
            ->assertSessionHasInput('phone', '77 383 13 64')
            ->assertSessionHasInput('public_email', 'awa@exemple.sn');
    }

    public function test_expiry_on_step_three_keeps_the_session_data_intact(): void
    {
        $this->seed(TemplateSeeder::class);

        $etat = ['completed' => [1, 2], 'data' => [
            'first_name' => 'Awa', 'last_name' => 'Ndiaye',
            'job_title' => 'Consultante', 'phone' => '+221773831364',
        ]];

        $this->actingAs(User::factory()->create())
            ->withSession(['profile_wizard' => $etat]);

        $this->postExpire(route('profile.store.step3'), [
            'template_id' => Template::first()->id,
            'primary_color' => '#0B3B2E',
        ])->assertRedirect(route('profile.create.step3'));

        // Les deux premières étapes sont toujours en session : rien n'est perdu.
        $this->assertSame($etat['data'], session('profile_wizard.data'));
        $this->assertDatabaseCount('profiles', 0);
    }

    /**
     * LE BROUILLON EN BASE SURVIT AUSSI À UN JETON EXPIRÉ.
     *
     * Un 419 en plein parcours ne doit rien coûter : ni la saisie de l'étape
     * en cours (repositionnée par withInput), ni les étapes déjà franchies
     * (en session ET en base).
     */
    public function test_expiry_never_touches_the_stored_draft(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        /*
         | On pose l'état de l'étape 1 directement : dans cette classe, le
         | contrôle CSRF est appliqué à TOUTES les requêtes, un POST « normal »
         | serait donc rejeté lui aussi. Ce qu'on veut éprouver ici n'est pas
         | l'écriture du brouillon (couverte par ClientAreaMatrixTest) mais sa
         | SURVIE à un 419.
         */
        $etat = ['completed' => [1], 'data' => [
            'first_name' => 'Awa', 'last_name' => 'Ndiaye', 'job_title' => 'Consultante',
        ]];

        ProfileDraft::create(['user_id' => $user->id, 'state' => $etat, 'next_step' => 2]);

        $this->actingAs($user)->withSession(['profile_wizard' => $etat]);

        $avant = ProfileDraft::where('user_id', $user->id)->firstOrFail();

        // Puis un jeton périmé sur l'étape 2.
        $this->postExpire(route('profile.store.step2'), ['phone' => '77 383 13 64'])
            ->assertRedirect(route('profile.create.step2'))
            ->assertSessionHasInput('phone', '77 383 13 64');

        $apres = ProfileDraft::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($avant->state, $apres->state, 'Le brouillon a été altéré par un 419.');
        $this->assertSame(2, $apres->next_step);
        $this->assertDatabaseCount('profiles', 0);
    }

    // -----------------------------------------------------------------------
    // Authentification
    // -----------------------------------------------------------------------

    public function test_expiry_on_login_keeps_the_email_but_never_the_password(): void
    {
        $this->postExpire('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasInput('email', 'awa@exemple.sn');

        $this->assertNull(session('_old_input.password'), 'Un mot de passe ne doit jamais être repositionné.');
        $this->assertGuest();
    }

    public function test_expiry_on_registration_keeps_the_form(): void
    {
        $this->postExpire('/register', [
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '77 383 13 64',
            'password' => 'motdepasse-solide-123',
        ])
            ->assertRedirect(route('register'))
            ->assertSessionHasInput('name', 'Awa Ndiaye')
            ->assertSessionHasInput('phone', '77 383 13 64');

        $this->assertNull(session('_old_input.password'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_expiry_on_password_request_keeps_the_email(): void
    {
        $this->postExpire('/forgot-password', ['email' => 'awa@exemple.sn'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasInput('email', 'awa@exemple.sn');
    }

    /** Une déconnexion au jeton périmé ne bloque pas : l'utilisateur voulait partir. */
    public function test_expiry_on_logout_leads_to_login_not_to_an_error_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postExpire('/logout')->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------------
    // Compte
    // -----------------------------------------------------------------------

    public function test_expiry_on_account_update_returns_to_the_account_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('compte.edit'))
            ->post(route('compte.edit'), [
                '_method' => 'PATCH',
                '_token' => 'jeton-perime',
                'name' => 'Awa Ndiaye',
            ])
            ->assertRedirect(route('compte.edit'))
            ->assertSessionHasInput('name', 'Awa Ndiaye');
    }

    // -----------------------------------------------------------------------
    // Garde-fous
    // -----------------------------------------------------------------------

    /** Jamais de redirection hors du site, même si le Referer le demande. */
    public function test_an_external_referer_never_becomes_a_redirect_target(): void
    {
        $this->actingAs(User::factory()->create());

        $this->withHeader('referer', 'https://exemple-malveillant.test/page')
            ->post(route('abonnement.checkout'), ['_token' => 'jeton-perime'])
            ->assertStatus(419);   // page d'erreur, pas une redirection ouverte
    }

    /** Une requête XHR reçoit un 419 en JSON, pas une redirection. */
    public function test_an_xhr_request_receives_json(): void
    {
        $this->postJson('/login', ['_token' => 'jeton-perime'])
            ->assertStatus(419)
            ->assertJsonStructure(['message']);
    }
}
