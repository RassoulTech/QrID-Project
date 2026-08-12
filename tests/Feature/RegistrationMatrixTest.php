<?php

namespace Tests\Feature;

use App\Mail\AlreadyRegisteredMail;
use App\Mail\ConfirmRegistrationMail;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Matrice complète du parcours d'inscription.
 *
 * GARANTIE CENTRALE : la réponse HTTP est IDENTIQUE dans tous les cas
 * (302 vers registration.pending). Seul l'e-mail envoyé diffère.
 */
class RegistrationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'motdepasse-solide-123';

    /** Soumet le formulaire avec un jeton d'idempotence valide. */
    private function register(string $email, string $name = 'Test User'): TestResponse
    {
        $token = Str::uuid()->toString();

        return $this->withSession(['registration.idem' => $token])->post('/register', [
            'name' => $name,
            'email' => $email,
            'phone' => '77 383 13 64',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            '_idem' => $token,
        ]);
    }

    private function makePending(string $email, array $overrides = []): PendingRegistration
    {
        return PendingRegistration::create(array_merge([
            'name' => 'Test User',
            'email' => $email,
            'phone' => '+221773831364',
            'password' => Hash::make(self::PASSWORD),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => Carbon::now()->addHour(),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'resend_count' => 0,
            'last_sent_at' => Carbon::now()->subMinutes(5),
            'created_at' => Carbon::now(),
        ], $overrides));
    }

    // -----------------------------------------------------------------------
    // CAS 1 — adresse totalement inconnue
    // -----------------------------------------------------------------------
    public function test_case_1_unknown_email_creates_pending_and_sends_confirmation(): void
    {
        Mail::fake();

        $this->register('inconnu@example.com')
            ->assertRedirect(route('registration.pending'));

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'inconnu@example.com']);

        Mail::assertSent(ConfirmRegistrationMail::class);
        Mail::assertNotQueued(AlreadyRegisteredMail::class);
    }

    // -----------------------------------------------------------------------
    // CAS 2 — un compte existe déjà
    // -----------------------------------------------------------------------
    public function test_case_2_existing_account_creates_nothing_and_sends_already_registered(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'connu@example.com']);

        $this->register('connu@example.com')
            ->assertRedirect(route('registration.pending')); // écran IDENTIQUE

        $this->assertDatabaseCount('users', 1);              // rien créé
        $this->assertDatabaseCount('pending_registrations', 0);

        Mail::assertSent(AlreadyRegisteredMail::class);
        Mail::assertNotQueued(ConfirmRegistrationMail::class);
    }

    // -----------------------------------------------------------------------
    // CAS 3 — demande en attente non expirée : rafraîchie, pas dupliquée
    // -----------------------------------------------------------------------
    public function test_case_3_valid_pending_is_refreshed_not_duplicated(): void
    {
        Mail::fake();

        $pending = $this->makePending('encours@example.com');
        $originalHash = $pending->token_hash;

        $this->register('encours@example.com')
            ->assertRedirect(route('registration.pending'));

        $this->assertDatabaseCount('pending_registrations', 1);

        $pending->refresh();
        $this->assertNotSame($originalHash, $pending->token_hash); // jeton régénéré
        $this->assertSame(1, $pending->resend_count);

        Mail::assertSent(ConfirmRegistrationMail::class);
    }

    // -----------------------------------------------------------------------
    // CAS 3 bis — limite de renvois atteinte : même écran, aucun e-mail
    // -----------------------------------------------------------------------
    public function test_case_3bis_resend_limit_shows_same_screen_without_sending(): void
    {
        Mail::fake();

        $this->makePending('limite@example.com', [
            'resend_count' => config('registration.max_resends'),
        ]);

        $this->register('limite@example.com')
            ->assertRedirect(route('registration.pending')); // écran IDENTIQUE

        Mail::assertNothingSent();
    }

    // -----------------------------------------------------------------------
    // CAS 4 — demande expirée : remplacée par une nouvelle
    // -----------------------------------------------------------------------
    public function test_case_4_expired_pending_is_replaced(): void
    {
        Mail::fake();

        $old = $this->makePending('expire@example.com', [
            'expires_at' => Carbon::now()->subHour(),
            'resend_count' => 2,
        ]);

        $this->register('expire@example.com')
            ->assertRedirect(route('registration.pending'));

        $this->assertDatabaseCount('pending_registrations', 1);
        $this->assertDatabaseMissing('pending_registrations', ['id' => $old->id]);

        $fresh = PendingRegistration::firstOrFail();
        $this->assertSame(0, $fresh->resend_count);       // compteur remis à zéro
        $this->assertTrue($fresh->expires_at->isFuture());

        Mail::assertSent(ConfirmRegistrationMail::class);
    }

    // -----------------------------------------------------------------------
    // CAS 5 — compte non vérifié : impossible par construction
    // -----------------------------------------------------------------------
    public function test_case_5_unverified_account_is_treated_as_existing_account(): void
    {
        Mail::fake();

        // État théoriquement impossible (aucun User n'est créé avant validation),
        // simulé ici pour couvrir d'éventuelles données héritées.
        User::factory()->unverified()->create(['email' => 'nonverifie@example.com']);

        $this->register('nonverifie@example.com')
            ->assertRedirect(route('registration.pending'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('pending_registrations', 0);

        Mail::assertSent(AlreadyRegisteredMail::class);
    }

    // -----------------------------------------------------------------------
    // CAS 6 — lien déjà consommé
    // -----------------------------------------------------------------------
    public function test_case_6_consumed_link_redirects_to_login_without_alarm(): void
    {
        $raw = Str::random(64);
        $this->makePending('conso@example.com', ['token_hash' => hash('sha256', $raw)]);

        $this->get(route('registration.confirm', ['token' => $raw]))
            ->assertRedirect(route('dashboard'));

        // Second clic : le jeton n'existe plus.
        $this->post(route('logout'));

        // Message NEUTRE : ni « bravo » ni alarme. « info », pas « success ».
        $this->get(route('registration.confirm', ['token' => $raw]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('info');

        $this->assertDatabaseCount('users', 1); // aucun doublon
    }

    // -----------------------------------------------------------------------
    // CAS 7 — lien expiré
    // -----------------------------------------------------------------------
    public function test_case_7_expired_link_shows_dedicated_page(): void
    {
        $raw = Str::random(64);
        $this->makePending('vieux@example.com', [
            'token_hash' => hash('sha256', $raw),
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        // La page a désormais sa propre URL : on y est redirigé, elle n'est
        // plus rendue au bout d'un lien mort (rechargeable, partageable).
        $this->get(route('registration.confirm', ['token' => $raw]))
            ->assertRedirect(route('registration.expired'));

        $this->get(route('registration.expired'))
            ->assertOk()
            ->assertSee('Ce lien a expiré');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    // -----------------------------------------------------------------------
    // CAS 8 — clic alors qu'une session est déjà ouverte
    // -----------------------------------------------------------------------
    public function test_case_8_confirming_while_logged_in_switches_account(): void
    {
        $other = User::factory()->create(['email' => 'autre@example.com']);

        $raw = Str::random(64);
        $this->makePending('nouveau@example.com', ['token_hash' => hash('sha256', $raw)]);

        $this->actingAs($other)
            ->get(route('registration.confirm', ['token' => $raw]))
            ->assertRedirect(route('dashboard'));

        // La session précédente a été fermée, le nouveau compte est connecté.
        $new = User::where('email', 'nouveau@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($new);
    }

    // -----------------------------------------------------------------------
    // CAS 9 — adresse jetable acceptée, format seul contrôlé
    // -----------------------------------------------------------------------
    public function test_case_9_disposable_domains_are_accepted(): void
    {
        Mail::fake();

        $this->register('jetable@gwshare.com')
            ->assertRedirect(route('registration.pending'));

        $this->assertDatabaseHas('pending_registrations', ['email' => 'jetable@gwshare.com']);
        Mail::assertSent(ConfirmRegistrationMail::class);
    }

    public function test_case_9_invalid_email_format_is_rejected(): void
    {
        Mail::fake();

        $this->register('pas-une-adresse')->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    // -----------------------------------------------------------------------
    // GARANTIE TRANSVERSALE — écran identique dans tous les cas
    // -----------------------------------------------------------------------
    public function test_all_cases_render_the_exact_same_screen(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'existe@example.com']);
        $this->makePending('attente@example.com');

        /*
         | TEMPS FIGÉ, et c'est indispensable.
         |
         | La page affiche un compte à rebours calculé sur last_sent_at. Sans
         | ce gel, il suffit qu'une seconde s'écoule entre la première et la
         | troisième itération pour que data-resend-wait passe de 60 à 59 : les
         | pages diffèrent alors d'un chiffre et le test échoue au hasard, en
         | accusant une fuite d'information qui n'existe pas.
         |
         | Ce défaut était latent ; il ne se déclenchait que lorsque la suite
         | devenait assez lente pour franchir une seconde ici.
         */
        $this->travelTo(now());

        $normalised = [];

        foreach (['neuf@example.com', 'existe@example.com', 'attente@example.com'] as $email) {
            $this->register($email)->assertRedirect(route('registration.pending'));

            $body = $this->withSession(['registration.pending_email' => $email])
                ->get(route('registration.pending'))
                ->assertOk()
                ->getContent();

            // On neutralise ce qui varie légitimement : adresse masquée,
            // jeton CSRF, et lien d'aide au développement.
            $body = preg_replace('/[a-z0-9._%+-]*\*+[a-z0-9._%+-]*@[^\s<]+/i', 'EMAIL', $body);
            $body = preg_replace('/name="_token" value="[^"]+"/', 'CSRF', $body);
            $body = preg_replace('#/inscription/confirmer/[^\s"<]+#', 'DEV_LINK', $body);

            $normalised[] = $body;
        }

        // Toutes les pages doivent être STRICTEMENT identiques une fois
        // neutralisés ces éléments : aucun indice sur l'état de l'adresse.
        $this->assertSame($normalised[0], $normalised[1]);
        $this->assertSame($normalised[0], $normalised[2]);
    }
}
