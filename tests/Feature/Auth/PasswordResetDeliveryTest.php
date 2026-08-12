<?php

namespace Tests\Feature\Auth;

use App\Mail\ConfirmRegistrationMail;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * LE LIEN DE RÉINITIALISATION PART TOUT DE SUITE — garde-fou.
 *
 * Ce test existe à cause d'une panne réelle en production.
 *
 * `sendPasswordResetNotification()` appelait Mail::queue(). Le message
 * partait dans la table `jobs` et n'en ressortait que si un worker exécutait
 * `queue:work`. Or aucun worker ne tourne sur le plan gratuit de Render.
 *
 * Résultat : la page répondait « lien envoyé », le jeton était créé, le délai
 * de sécurité s'armait — et RIEN N'ARRIVAIT. Aucune erreur, aucune trace.
 * L'utilisateur recliquait, tombait sur « Merci de patienter », et concluait
 * que l'application était cassée. Il a fallu plusieurs allers-retours pour
 * l'identifier, faute d'un test qui aurait dit la vérité en une seconde.
 *
 * La réinitialisation est le PIRE endroit où différer un envoi : quelqu'un
 * qui ne peut plus se connecter attend devant sa boîte.
 */
class PasswordResetDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function compte(): User
    {
        return User::factory()->create([
            'email' => 'essai@qrid.sn',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * LE TEST CENTRAL — le pilote de file est volontairement mis sur
     * « database », c'est-à-dire la configuration qui provoquait la panne.
     * L'e-mail doit partir quand même.
     */
    public function test_the_reset_link_is_sent_immediately_even_with_a_queued_driver(): void
    {
        config(['queue.default' => 'database']);
        Mail::fake();

        $this->compte();

        $statut = Password::sendResetLink(['email' => 'essai@qrid.sn']);

        $this->assertSame(Password::RESET_LINK_SENT, $statut);

        Mail::assertSent(ResetPasswordMail::class);

        // Le cœur du garde-fou : rien ne doit attendre un worker.
        Mail::assertNotQueued(ResetPasswordMail::class);
    }

    /** Aucun travail en attente : la table `jobs` reste vide. */
    public function test_nothing_is_left_waiting_for_a_worker(): void
    {
        config(['queue.default' => 'database']);
        Mail::fake();

        $this->compte();
        Password::sendResetLink(['email' => 'essai@qrid.sn']);

        $this->assertSame(
            0,
            DB::table('jobs')->count(),
            'Un travail attend un worker qui n\'existe pas : l\'e-mail ne partira jamais.'
        );
    }

    /** Le jeton est bien créé — le lien envoyé mène quelque part. */
    public function test_a_token_is_created_for_the_link(): void
    {
        Mail::fake();
        $this->compte();

        Password::sendResetLink(['email' => 'essai@qrid.sn']);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'essai@qrid.sn']);
    }

    /**
     * Le message d'attente doit ORIENTER, pas seulement refuser.
     *
     * L'ancien texte — « Merci de patienter avant de réessayer » — ne disait
     * ni pourquoi, ni combien de temps, ni qu'un lien était déjà parti.
     * Devant lui, on reclique, ce qui relance le délai.
     */
    public function test_the_throttle_message_tells_the_user_what_to_do(): void
    {
        $message = trans('passwords.throttled');

        $this->assertStringContainsString('indésirables', $message);
        $this->assertStringContainsString('minute', $message);
    }

    /**
     * Une adresse inconnue ne doit rien révéler.
     *
     * Répondre « ce compte n'existe pas » transformerait ce formulaire en
     * outil pour savoir qui est client.
     */
    public function test_an_unknown_address_reveals_nothing(): void
    {
        Mail::fake();

        $reponse = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'inconnu@qrid.sn']);

        Mail::assertNothingSent();
        $reponse->assertRedirect();
    }

    /**
     * L'INSCRIPTION AUSSI ÉTAIT MORTE.
     *
     * ConfirmRegistrationMail hérite de la même classe de base : il était donc
     * mis en file lui aussi, et le lien de confirmation n'arrivait jamais.
     * Autrement dit, plus aucun compte ne pouvait être créé en production —
     * et rien ne le signalait.
     */
    public function test_the_registration_confirmation_is_sent_immediately(): void
    {
        config(['queue.default' => 'database']);
        Mail::fake();

        // Le formulaire exige un téléphone et un jeton d'idempotence, qui
        // empêche une double soumission de créer deux inscriptions.
        $jeton = 'jeton-idempotence-essai';

        $this->withSession(['registration.idem' => $jeton])
            ->post('/register', [
                'name' => 'Awa Ndiaye',
                'email' => 'awa@qrid.sn',
                'phone' => '+221 78 637 93 02',
                'password' => 'motdepasse-solide-2026',
                'password_confirmation' => 'motdepasse-solide-2026',
                '_idem' => $jeton,
            ]);

        Mail::assertSent(ConfirmRegistrationMail::class);
        Mail::assertNotQueued(ConfirmRegistrationMail::class);

        $this->assertSame(
            0,
            DB::table('jobs')->count(),
            'Le lien de confirmation attend un worker : aucun compte ne pourra être créé.'
        );
    }
}
