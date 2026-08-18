<?php

namespace Tests\Feature\Notifications;

use App\Mail\AdminAlertMail;
use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSucceededMail;
use App\Mail\ProfilePublishedMail;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\User;
use App\Services\Payment\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * BLOC 1 — les e-mails du parcours de paiement.
 *
 * C'est le point le plus délicat du bloc, pour une raison d'argent : ces
 * envois se produisent autour d'une transaction qui déplace des fonds. Un
 * e-mail qui lève une exception au mauvais endroit annulerait l'encaissement
 * et laisserait un client débité sans abonnement.
 */
class PaymentMailFlowTest extends TestCase
{
    use RefreshDatabase;

    private Plan $formule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formule = Plan::factory()->create([
            'slug' => 'standard',
            'name' => 'Standard',
            'duration_days' => 30,
            'price_fcfa' => 2500,
            'is_active' => true,
        ]);
    }

    private function paiementOuvert(User $user): Payment
    {
        $checkout = app(CheckoutService::class);

        $payment = $checkout->start($user, $this->formule, Payment::METHODS[0] ?? 'wave');

        // La référence d'opérateur naît à l'ouverture ; sans elle, aucun
        // retour ne peut être confirmé.
        $checkout->redirectUrl($payment);

        return $payment->refresh();
    }

    // =======================================================================
    // ENCAISSEMENT
    // =======================================================================

    public function test_a_successful_payment_sends_a_receipt_carrying_the_amount(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'client@qrid.sn']);
        Profile::factory()->for($user)->create(['slug' => 'awa-ndiaye', 'is_active' => false]);

        $payment = $this->paiementOuvert($user);

        app(CheckoutService::class)->succeed($payment, [
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]);

        Mail::assertSent(PaymentSucceededMail::class, function (PaymentSucceededMail $m) {
            return $m->hasTo('client@qrid.sn')
                && $m->montant === '2 500'
                && $m->reference !== '';
        });
    }

    /** L'équipe est prévenue, avec le montant et le moyen. */
    public function test_a_successful_payment_alerts_the_team(): void
    {
        Mail::fake();

        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'equipe@qrid.sn']);

        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['is_active' => false]);

        $payment = $this->paiementOuvert($user);

        app(CheckoutService::class)->succeed($payment, [
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]);

        Mail::assertSent(AdminAlertMail::class, function (AdminAlertMail $m) {
            return $m->hasTo('equipe@qrid.sn')
                && ($m->lignes['Montant'] ?? '') === '2 500 FCFA';
        });
    }

    /**
     * Payer publie la carte : le client doit recevoir SON LIEN.
     *
     * Sans cet envoi, quelqu'un qui vient de payer 2 500 FCFA doit se
     * reconnecter pour découvrir l'adresse qu'il est censé partager.
     */
    public function test_a_payment_that_publishes_the_card_also_sends_the_link(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['is_active' => false]);

        $payment = $this->paiementOuvert($user);

        app(CheckoutService::class)->succeed($payment, [
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]);

        Mail::assertSent(ProfilePublishedMail::class);
    }

    /**
     * UN RENOUVELLEMENT N'EST PAS UNE PUBLICATION.
     *
     * La carte était déjà en ligne : « votre carte est en ligne » n'apprend
     * rien à quelqu'un qui vient de renouveler. Le reçu, lui, part toujours.
     */
    public function test_renewing_does_not_re_announce_an_already_published_card(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['is_active' => true]);

        $payment = $this->paiementOuvert($user);

        app(CheckoutService::class)->succeed($payment, [
            'statut' => 'succes',
            'reference' => $payment->provider_ref,
        ]);

        Mail::assertNotSent(ProfilePublishedMail::class);
        Mail::assertSent(PaymentSucceededMail::class);
    }

    /** Un retour rejoué ne renvoie pas un second reçu. */
    public function test_a_replayed_callback_does_not_send_a_second_receipt(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['is_active' => false]);

        $payment = $this->paiementOuvert($user);
        $retour = ['statut' => 'succes', 'reference' => $payment->provider_ref];

        app(CheckoutService::class)->succeed($payment, $retour);
        app(CheckoutService::class)->succeed($payment->refresh(), $retour);

        Mail::assertSent(PaymentSucceededMail::class, 1);
    }

    // =======================================================================
    // ÉCHEC
    // =======================================================================

    /**
     * LA PREMIÈRE CHOSE À DIRE EST QU'AUCUNE SOMME N'A ÉTÉ PRÉLEVÉE.
     *
     * C'est la seule question que se pose le client, et tant qu'elle n'a pas
     * de réponse il ne lit pas la suite.
     */
    public function test_a_failed_payment_states_that_nothing_was_charged(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'client@qrid.sn']);

        $payment = $this->paiementOuvert($user);

        app(CheckoutService::class)->fail($payment, 'retour non confirmé par la passerelle');

        Mail::assertSent(PaymentFailedMail::class, function (PaymentFailedMail $m) {
            return $m->hasTo('client@qrid.sn')
                && str_contains($m->render(), 'Aucune somme n\'a été prélevée');
        });
    }

    /**
     * LA RAISON TECHNIQUE NE SORT PAS VERS LE CLIENT.
     *
     * Elle n'aide personne, inquiète tout le monde, et renseigne un tiers sur
     * notre infrastructure. Elle appartient à l'alerte d'équipe.
     */
    public function test_the_technical_reason_reaches_the_team_and_not_the_client(): void
    {
        Mail::fake();

        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'equipe@qrid.sn']);
        $user = User::factory()->create(['email' => 'client@qrid.sn']);

        $payment = $this->paiementOuvert($user);

        app(CheckoutService::class)->fail($payment, 'passerelle indisponible');

        Mail::assertSent(PaymentFailedMail::class, function (PaymentFailedMail $m) {
            return ! str_contains($m->render(), 'passerelle indisponible');
        });

        Mail::assertSent(AdminAlertMail::class, function (AdminAlertMail $m) {
            return ($m->lignes['Raison'] ?? '') === 'passerelle indisponible';
        });
    }

    /** Un échec rejoué n'écrit pas deux fois au client. */
    public function test_a_replayed_failure_does_not_write_twice(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $payment = $this->paiementOuvert($user);

        app(CheckoutService::class)->fail($payment, 'abandon');
        app(CheckoutService::class)->fail($payment->refresh(), 'abandon');

        Mail::assertSent(PaymentFailedMail::class, 1);
    }
}
