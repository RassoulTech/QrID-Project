<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Réinitialisation du mot de passe.
 *
 * Le produit n'envoie PAS la notification ResetPassword du framework :
 * User::sendPasswordResetNotification() met en file le Mailable
 * ResetPasswordMail (français, à l'identité du produit, mis en file comme tous
 * les autres e-mails). Ces tests observent donc la file d'e-mails, et non la
 * file de notifications — c'est ce qui manquait ici.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Mail::assertQueued(
            ResetPasswordMail::class,
            fn (ResetPasswordMail $mail) => $mail->hasTo($user->email)
        );
    }

    /** L'adresse inconnue ne doit rien envoyer — et ne rien révéler. */
    public function test_an_unknown_address_receives_no_mail(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'personne@exemple.sn']);

        Mail::assertNothingQueued();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        // Le lien de l'e-mail doit s'ouvrir tel quel, sans retouche.
        $this->get($this->lienDeReinitialisation())->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->post('/reset-password', [
            'token' => $this->jetonDeReinitialisation(),
            'email' => $user->email,
            'password' => 'nouveau-mot-de-passe-123',
            'password_confirmation' => 'nouveau-mot-de-passe-123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(
            Hash::check('nouveau-mot-de-passe-123', $user->fresh()->password),
            'Le nouveau mot de passe doit être enregistré, haché.'
        );
    }

    /** Un jeton fabriqué de toutes pièces ne réinitialise rien. */
    public function test_an_invalid_token_is_refused(): void
    {
        Mail::fake();

        $user = User::factory()->create(['password' => 'motdepasse-solide-123']);

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->from(route('password.reset', ['token' => 'jeton-invente']))
            ->post('/reset-password', [
                'token' => 'jeton-invente',
                'email' => $user->email,
                'password' => 'nouveau-mot-de-passe-123',
                'password_confirmation' => 'nouveau-mot-de-passe-123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(
            Hash::check('motdepasse-solide-123', $user->fresh()->password),
            'L\'ancien mot de passe doit rester en place.'
        );
    }

    // -----------------------------------------------------------------------

    /** L'URL exacte contenue dans l'e-mail mis en file. */
    private function lienDeReinitialisation(): string
    {
        $mail = Mail::queued(ResetPasswordMail::class)->first();

        $this->assertNotNull($mail, 'Aucun e-mail de réinitialisation n\'a été mis en file.');

        return $mail->resetUrl;
    }

    private function jetonDeReinitialisation(): string
    {
        $chemin = parse_url($this->lienDeReinitialisation(), PHP_URL_PATH);

        return basename((string) $chemin);
    }
}
