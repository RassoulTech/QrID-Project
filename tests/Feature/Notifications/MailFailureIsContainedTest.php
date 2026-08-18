<?php

namespace Tests\Feature\Notifications;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use App\Models\MailLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\User;
use App\Services\Payment\CheckoutService;
use App\Support\Courrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * UNE PANNE DE MESSAGERIE NE DOIT RIEN CASSER D'AUTRE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE TEST EXISTE À CAUSE D'UNE PANNE RÉELLE
 * ═══════════════════════════════════════════════════════════════════════
 * En production, l'envoi SMTP a cessé de répondre. La demande de
 * réinitialisation de mot de passe rendait alors une erreur 500 : une page qui
 * n'apprend rien, et qui fait croire que le formulaire lui-même est cassé.
 *
 * Le même défaut, placé sur le chemin du paiement, aurait été bien pire :
 * l'exception aurait remonté à travers la transaction et ANNULÉ
 * L'ENCAISSEMENT. Le client débité par l'opérateur, sans abonnement chez nous,
 * et rien dans les données pour dire pourquoi.
 *
 * Toute la classe Courrier existe pour cela. Ces tests constatent qu'elle
 * tient — et, tout aussi important, qu'elle ne CACHE pas la panne : chaque
 * échec laisse une trace en base, visible sur l'écran « État système ».
 */
class MailFailureIsContainedTest extends TestCase
{
    use RefreshDatabase;

    /** Rend tout envoi impossible, comme un serveur SMTP qui ne répond plus. */
    private function messagerieEnPanne(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP injoignable'));
    }

    // =======================================================================
    // LE CONTRAT DE Courrier
    // =======================================================================

    public function test_a_broken_transport_never_throws(): void
    {
        $this->messagerieEnPanne();

        $parti = Courrier::informer('client@qrid.sn', new WelcomeMail(
            name: 'Awa',
            createUrl: 'https://exemple.test/creer',
            trialDays: 15,
        ));

        $this->assertFalse($parti, 'Courrier doit signaler l\'échec par sa valeur de retour, pas par une exception.');
    }

    /** La panne est avalée pour l'utilisateur, JAMAIS pour l'exploitant. */
    public function test_a_failure_leaves_a_trace_in_the_database(): void
    {
        $this->messagerieEnPanne();

        Courrier::informer('client@qrid.sn', new WelcomeMail(
            name: 'Awa',
            createUrl: 'https://exemple.test/creer',
            trialDays: 15,
        ));

        $this->assertDatabaseHas('mail_logs', [
            'recipient' => 'client@qrid.sn',
            'status' => 'failed',
        ]);

        $trace = MailLog::failed()->latest('id')->first();

        $this->assertStringContainsString('SMTP injoignable', (string) $trace->error);
    }

    /** Sans destinataire, il n'y a rien à faire — et rien à signaler. */
    public function test_an_empty_recipient_is_a_silent_no_op(): void
    {
        Mail::fake();

        $this->assertFalse(Courrier::informer('', new WelcomeMail(
            name: 'Awa',
            createUrl: 'https://exemple.test/creer',
            trialDays: 15,
        )));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('mail_logs', 0);
    }

    // =======================================================================
    // LE CAS QUI COÛTE DE L'ARGENT
    // =======================================================================

    /**
     * L'ENCAISSEMENT SURVIT À UNE MESSAGERIE MORTE.
     *
     * C'est le test le plus important du bloc. Le paiement doit être marqué
     * réussi, l'abonnement ouvert et la carte publiée, même si aucun e-mail
     * ne peut partir. L'argent reçu ne se défait pas parce qu'un message
     * n'est pas parti.
     */
    public function test_a_payment_is_still_cashed_when_no_email_can_leave(): void
    {
        $formule = Plan::factory()->create([
            'slug' => 'standard',
            'name' => 'Standard',
            'duration_days' => 30,
            'price_fcfa' => 2500,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create(['is_active' => false]);

        $checkout = app(CheckoutService::class);
        $payment = $checkout->start($user, $formule, Payment::METHODS[0] ?? 'wave');
        $checkout->redirectUrl($payment);
        $payment->refresh();

        // La messagerie tombe APRÈS l'ouverture du paiement, comme dans la vie.
        $this->messagerieEnPanne();

        $encaisse = $checkout->succeed($payment, [
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]);

        $this->assertTrue($encaisse, 'Le paiement doit aboutir malgré la panne d\'envoi.');

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->refresh()->status);
        $this->assertTrue($profile->refresh()->is_active, 'La carte doit être publiée.');
        $this->assertNotNull($user->refresh()->activeSubscription(), 'L\'abonnement doit être ouvert.');
    }

    /**
     * Une inscription confirmée aboutit même si la bienvenue ne part pas.
     *
     * L'essai gratuit s'ouvre, le compte existe. Un e-mail de confort ne peut
     * pas coûter un client.
     */
    public function test_a_confirmed_account_survives_a_broken_mailer(): void
    {
        Plan::factory()->create([
            'slug' => 'essai-gratuit',
            'name' => 'Essai gratuit',
            'duration_days' => 15,
            'price_fcfa' => 0,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->messagerieEnPanne();

        event(new UserRegistered($user));

        $this->assertNotNull(
            $user->refresh()->activeSubscription(),
            'L\'essai gratuit doit s\'ouvrir même si l\'e-mail de bienvenue échoue.'
        );
    }
}
