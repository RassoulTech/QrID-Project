<?php

namespace Tests\Feature\Notifications;

use App\Events\PasswordChanged;
use App\Events\ProfileCreated;
use App\Events\ProfilePublished;
use App\Events\UserRegistered;
use App\Mail\AdminAlertMail;
use App\Mail\PasswordChangedMail;
use App\Mail\ProfilePublishedMail;
use App\Mail\WelcomeMail;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * BLOC 1 — les e-mails déclenchés par un événement métier.
 *
 * Ce que ces tests protègent : le fait qu'un e-mail PARTE, et qu'il parte à la
 * bonne personne. La panne de production qui a coûté plusieurs jours n'était
 * pas un défaut de rédaction — c'était un message qui n'existait nulle part
 * ailleurs que dans une table `jobs` que personne ne vidait.
 */
class ClientMailFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Le plan d'essai conditionne l'ouverture de l'abonnement gratuit.
        Plan::factory()->create([
            'slug' => 'essai-gratuit',
            'name' => 'Essai gratuit',
            'duration_days' => 15,
            'price_fcfa' => 0,
            'is_active' => true,
        ]);
    }

    // =======================================================================
    // COMPTE CONFIRMÉ
    // =======================================================================

    public function test_a_confirmed_account_receives_a_welcome_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'awa@qrid.sn', 'name' => 'Awa Ndiaye']);

        event(new UserRegistered($user));

        Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $m) => $m->hasTo('awa@qrid.sn'));

        // Rien ne doit attendre un worker : il n'en existe aucun.
        Mail::assertNotQueued(WelcomeMail::class);
    }

    /**
     * L'e-mail de bienvenue annonce une date d'échéance d'essai.
     *
     * StartFreeTrial et WelcomeNewClient écoutent le MÊME événement, sans
     * ordre garanti. Ce test constate que la date est bien présente — donc que
     * l'essai est lu en base et non supposé.
     */
    public function test_the_welcome_email_carries_the_trial_end_date(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        event(new UserRegistered($user));

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $m) {
            return $m->trialDays === 15 && $m->trialEndsAt !== null;
        });
    }

    /**
     * L'essai absent ne doit pas empêcher la bienvenue.
     *
     * Sans plan « essai-gratuit », StartFreeTrial abandonne. Le message doit
     * partir malgré tout, SANS date — annoncer une échéance qui n'existe pas
     * serait pire que de n'en annoncer aucune.
     */
    public function test_the_welcome_email_still_leaves_without_a_trial(): void
    {
        Mail::fake();

        Plan::where('slug', 'essai-gratuit')->delete();

        $user = User::factory()->create();

        event(new UserRegistered($user));

        Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $m) => $m->trialEndsAt === null);
    }

    // =======================================================================
    // CARTE CRÉÉE — l'équipe seule est prévenue
    // =======================================================================

    /**
     * Le client vient de valider la dernière étape et regarde sa carte à
     * l'écran : lui écrire « votre carte est créée » n'apprendrait rien.
     */
    public function test_creating_a_card_alerts_the_team_and_writes_nothing_to_the_client(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'equipe@qrid.sn']);
        $client = User::factory()->create(['email' => 'client@qrid.sn']);
        $profile = Profile::factory()->for($client)->create(['is_active' => false]);

        event(new ProfileCreated($profile));

        Mail::assertSent(AdminAlertMail::class, fn (AdminAlertMail $m) => $m->hasTo('equipe@qrid.sn'));

        Mail::assertNotSent(AdminAlertMail::class, fn (AdminAlertMail $m) => $m->hasTo('client@qrid.sn'));

        $this->assertNotNull($admin->id);
    }

    // =======================================================================
    // CARTE PUBLIÉE
    // =======================================================================

    public function test_publishing_a_card_sends_the_public_link_to_the_client(): void
    {
        Mail::fake();

        $client = User::factory()->create(['email' => 'client@qrid.sn']);
        $profile = Profile::factory()->for($client)->create([
            'slug' => 'awa-ndiaye',
            'is_active' => true,
        ]);

        event(new ProfilePublished($profile));

        Mail::assertSent(ProfilePublishedMail::class, function (ProfilePublishedMail $m) {
            return $m->hasTo('client@qrid.sn')
                && str_contains($m->publicUrl, 'awa-ndiaye');
        });
    }

    // =======================================================================
    // MOT DE PASSE — e-mail de sécurité
    // =======================================================================

    public function test_changing_the_password_warns_the_account_holder(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'client@qrid.sn']);

        event(new PasswordChanged($user, '41.82.10.5'));

        Mail::assertSent(PasswordChangedMail::class, function (PasswordChangedMail $m) {
            return $m->hasTo('client@qrid.sn') && $m->ip === '41.82.10.5';
        });
    }

    /**
     * Le message de sécurité doit porter une SORTIE DE SECOURS.
     *
     * Il s'adresse d'abord à quelqu'un qui n'a rien fait : sans lien de
     * reprise en main, il ne fait que constater la perte du compte.
     */
    public function test_the_security_email_offers_a_way_to_regain_control(): void
    {
        $user = User::factory()->create();

        $mail = new PasswordChangedMail(
            name: $user->name,
            date: '13 août 2026 à 10:00',
            ip: null,
            resetUrl: route('password.request'),
        );

        $rendu = $mail->render();

        $this->assertStringContainsString(route('password.request'), $rendu);
        $this->assertStringContainsString('Si ce n\'est pas vous', $rendu);
    }

    /**
     * Le changement depuis les paramètres déclenche aussi l'alerte.
     *
     * C'est le chemin que l'événement PasswordReset du framework ignore : sans
     * notre propre événement, ce parcours resterait muet.
     */
    public function test_the_settings_screen_also_triggers_the_security_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['password' => bcrypt('ancien-mot-de-passe-2026')]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'ancien-mot-de-passe-2026',
            'password' => 'nouveau-mot-de-passe-2026',
            'password_confirmation' => 'nouveau-mot-de-passe-2026',
        ]);

        Mail::assertSent(PasswordChangedMail::class);
    }
}
