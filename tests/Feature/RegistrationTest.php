<?php

namespace Tests\Feature;

use App\Mail\ConfirmRegistrationMail;
use App\Models\PendingRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_pending_row_with_all_columns(): void
    {
        Mail::fake();

        $token = 'test-idem-token';

        $response = $this->withSession(['registration.idem' => $token])
            ->post('/register', [
                'name' => 'Modou Faye',
                'email' => 'modou@example.com',
                'phone' => '+221 78 637 93 02',   // format libre, doit être normalisé
                'password' => 'motdepasse-solide-123',
                'password_confirmation' => 'motdepasse-solide-123',
                '_idem' => $token,
            ]);

        $response->assertRedirect(route('registration.pending'));

        // Aucun compte créé à ce stade.
        $this->assertDatabaseCount('users', 0);

        // Une demande en attente, au format canonique.
        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'modou@example.com',
            'phone' => '+221786379302',
            'resend_count' => 0,
        ]);

        // Toutes les colonnes sensibles sont bien renseignées.
        $pending = PendingRegistration::firstOrFail();
        $this->assertNotEmpty($pending->name);
        $this->assertNotEmpty($pending->password);          // hashé
        $this->assertNotSame('motdepasse-solide-123', $pending->password);
        $this->assertNotEmpty($pending->token_hash);
        $this->assertNotNull($pending->expires_at);
        $this->assertNotNull($pending->ip_hash);
        $this->assertNotNull($pending->created_at);

        Mail::assertQueued(ConfirmRegistrationMail::class);
    }

    /**
     * NON-RÉGRESSION — afficher la page de confirmation ne doit RIEN casser.
     *
     * Elle appelait devConfirmUrl(), qui régénérait token_hash en base à chaque
     * rendu. Le jeton envoyé par e-mail devenait donc invalide dès la
     * redirection qui suit l'inscription : le clic sur le lien reçu tombait sur
     * « ce lien a déjà été utilisé », aucun compte n'était créé, et « Renvoyer
     * l'e-mail » rejouait le même piège. Plus aucune inscription ne pouvait
     * aboutir en local.
     */
    public function test_viewing_the_confirmation_page_never_invalidates_the_emailed_link(): void
    {
        Mail::fake();

        $token = 'jeton-idem';

        $this->withSession(['registration.idem' => $token])->post('/register', [
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '77 383 13 64',
            'password' => 'motdepasse-solide-123',
            'password_confirmation' => 'motdepasse-solide-123',
            '_idem' => $token,
        ])->assertRedirect(route('registration.pending'));

        $hashEnvoye = PendingRegistration::firstOrFail()->token_hash;

        // On ouvre la page, on la recharge, on demande un renvoi impossible
        // (délai non écoulé) : le jeton parti par e-mail doit survivre.
        $this->get(route('registration.pending'))->assertOk();
        $this->get(route('registration.pending'))->assertOk();
        $this->post(route('registration.resend'));
        $this->get(route('registration.pending'))->assertOk();

        $this->assertSame(
            $hashEnvoye,
            PendingRegistration::firstOrFail()->token_hash,
            'Le jeton envoyé par e-mail a été invalidé par un simple affichage de page.'
        );
    }

    /** Le lien reçu par e-mail crée bien le compte après un détour sur la page. */
    public function test_the_emailed_link_still_works_after_visiting_the_confirmation_page(): void
    {
        Mail::fake();

        // On rejoue ce que fait le service : un jeton en clair, son sha256 en base.
        $raw = Str::random(64);

        PendingRegistration::create([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '+221773831364',
            'password' => Hash::make('motdepasse-solide-123'),
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addHour(),
            'resend_count' => 0,
            'last_sent_at' => now(),
            'created_at' => now(),
        ]);

        $this->withSession(['registration.pending_email' => 'awa@exemple.sn'])
            ->get(route('registration.pending'))
            ->assertOk();

        // Puis le clic sur le lien de l'e-mail : le compte doit naître.
        $this->get(route('registration.confirm', ['token' => $raw]))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'awa@exemple.sn']);
        $this->assertAuthenticated();
    }

    /**
     * Un lien de confirmation n'est JAMAIS expiré à la naissance.
     *
     * (int) env() rend 0 dès que la variable manque, est vide ou n'est pas
     * numérique — ce qui arrive quand le cache de configuration est reconstruit
     * sans le .env. Avec 0, expires_at valait « maintenant » : plus aucune
     * inscription ne pouvait aboutir, et rien ne le signalait. D'où le plancher
     * dans config/registration.php, que ce test verrouille.
     */
    public function test_a_fresh_confirmation_link_is_never_born_expired(): void
    {
        Mail::fake();

        $this->assertGreaterThanOrEqual(
            5,
            (int) config('registration.verification_ttl'),
            'La validité du lien doit être planchonnée : (int) env() peut rendre 0.'
        );

        $token = 'jeton-idem';

        $this->withSession(['registration.idem' => $token])->post('/register', [
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '77 383 13 64',
            'password' => 'motdepasse-solide-123',
            'password_confirmation' => 'motdepasse-solide-123',
            '_idem' => $token,
        ]);

        $this->assertFalse(
            PendingRegistration::firstOrFail()->isExpired(),
            'Le lien vient d\'être créé et il est déjà expiré.'
        );
    }

    public function test_duplicate_submission_with_same_token_does_not_create_two_rows(): void
    {
        Mail::fake();

        $token = 'test-idem-token';
        $payload = [
            'name' => 'Awa Ndiaye',
            'email' => 'awa@example.com',
            'phone' => '770000000',
            'password' => 'motdepasse-solide-123',
            'password_confirmation' => 'motdepasse-solide-123',
            '_idem' => $token,
        ];

        $this->withSession(['registration.idem' => $token])->post('/register', $payload);
        // Rejeu : le jeton a été consommé, aucune seconde ligne.
        $this->post('/register', $payload);

        $this->assertDatabaseCount('pending_registrations', 1);
    }
}
