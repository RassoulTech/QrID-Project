<?php

namespace Tests\Feature\Notifications;

use App\Mail\ProfileReminderMail;
use App\Mail\SubscriptionExpiredMail;
use App\Mail\SubscriptionExpiringMail;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * BLOC 1 — les relances déclenchées par le calendrier.
 *
 * Ce que ces tests protègent avant tout : LA NON-RÉPÉTITION.
 *
 * Rien n'empêche ces commandes de tourner deux fois — rattrapage après une
 * panne, planificateur relancé par un déploiement, exécution manuelle. Si la
 * mémoire des envois ne tenait pas, le même client recevrait deux ou trois
 * fois le même message. Le coût n'est pas la gêne : c'est le classement de
 * l'expéditeur en indésirable, qui se paie ensuite sur la réinitialisation de
 * mot de passe — c'est-à-dire sur quelqu'un qui ne peut plus se connecter.
 */
class ScheduledRemindersTest extends TestCase
{
    use RefreshDatabase;

    // =======================================================================
    // RAPPELS DE PUBLICATION — 24 h puis 72 h
    // =======================================================================

    private function carteNonPubliee(int $heures): Profile
    {
        $user = User::factory()->create();

        return Profile::factory()->for($user)->create([
            'is_active' => false,
            'reminder_count' => 0,
            'created_at' => now()->subHours($heures),
        ]);
    }

    public function test_a_card_left_unpublished_for_a_day_gets_a_first_reminder(): void
    {
        Mail::fake();

        $profil = $this->carteNonPubliee(30);

        $this->artisan('profiles:remind')->assertSuccessful();

        Mail::assertSent(ProfileReminderMail::class, fn (ProfileReminderMail $m) => $m->rang === 1);

        $this->assertSame(1, $profil->refresh()->reminder_count);
    }

    /** Une carte créée il y a deux heures n'est pas encore relancée. */
    public function test_a_fresh_card_is_left_alone(): void
    {
        Mail::fake();

        $this->carteNonPubliee(2);

        $this->artisan('profiles:remind')->assertSuccessful();

        Mail::assertNothingSent();
    }

    /**
     * LE CŒUR DU GARDE-FOU : deux exécutions consécutives n'envoient qu'un
     * message. C'est le scénario du rattrapage après incident.
     */
    public function test_running_twice_in_a_row_sends_a_single_reminder(): void
    {
        Mail::fake();

        $this->carteNonPubliee(30);

        $this->artisan('profiles:remind');
        $this->artisan('profiles:remind');

        Mail::assertSent(ProfileReminderMail::class, 1);
    }

    /** Le second rappel n'arrive qu'après 72 heures, et porte un autre ton. */
    public function test_the_second_reminder_waits_for_the_third_day(): void
    {
        Mail::fake();

        $profil = $this->carteNonPubliee(80);
        $profil->forceFill(['reminder_count' => 1])->save();

        $this->artisan('profiles:remind');

        Mail::assertSent(ProfileReminderMail::class, fn (ProfileReminderMail $m) => $m->rang === 2);
    }

    /**
     * JAMAIS DE TROISIÈME. Le compteur borne la séquence : aucune condition
     * de date ne peut rattraper un profil déjà relancé deux fois.
     */
    public function test_there_is_never_a_third_reminder(): void
    {
        Mail::fake();

        $profil = $this->carteNonPubliee(500);
        $profil->forceFill(['reminder_count' => 2])->save();

        $this->artisan('profiles:remind');

        Mail::assertNothingSent();
    }

    /** Une carte publiée entre-temps sort de la liste. */
    public function test_a_published_card_is_no_longer_reminded(): void
    {
        Mail::fake();

        $profil = $this->carteNonPubliee(30);
        $profil->forceFill(['is_active' => true])->save();

        $this->artisan('profiles:remind');

        Mail::assertNothingSent();
    }

    /**
     * Une carte coupée par l'administration ne doit pas être relancée : la
     * relance contredirait la décision prise et renverrait vers un écran qui
     * refusera.
     */
    public function test_a_card_deactivated_by_the_team_is_not_reminded(): void
    {
        Mail::fake();

        $profil = $this->carteNonPubliee(30);
        $profil->forceFill(['deactivated_at' => now()])->save();

        $this->artisan('profiles:remind');

        Mail::assertNothingSent();
    }

    /** La simulation n'écrit rien et n'envoie rien. */
    public function test_the_dry_run_changes_nothing(): void
    {
        Mail::fake();

        $profil = $this->carteNonPubliee(30);

        $this->artisan('profiles:remind --dry-run')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, $profil->refresh()->reminder_count);
    }

    // =======================================================================
    // ÉCHÉANCES D'ABONNEMENT
    // =======================================================================

    private function abonnement(int $joursAvantEcheance): Subscription
    {
        $plan = Plan::factory()->create(['slug' => 'mensuel', 'name' => 'Mensuel', 'duration_days' => 30]);

        return Subscription::factory()->create([
            'user_id' => User::factory()->create()->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->addDays($joursAvantEcheance),
            'status' => Subscription::STATUS_ACTIVE,
            'notified_at' => null,
        ]);
    }

    /** @return array<int, array{int}> */
    public static function paliers(): array
    {
        return [[7], [3], [1], [0]];
    }

    /**
     * Les quatre paliers du plan. Chacun doit produire exactement un message,
     * portant le bon nombre de jours restants.
     */
    #[DataProvider('paliers')]
    public function test_each_threshold_produces_its_own_reminder(int $jours): void
    {
        Mail::fake();

        $this->abonnement($jours);

        $this->artisan('subscriptions:notify')->assertSuccessful();

        Mail::assertSent(
            SubscriptionExpiringMail::class,
            fn (SubscriptionExpiringMail $m) => $m->joursRestants === $jours
        );
    }

    /** Un abonnement à échéance lointaine ne déclenche rien. */
    public function test_a_distant_deadline_is_silent(): void
    {
        Mail::fake();

        $this->abonnement(20);

        $this->artisan('subscriptions:notify');

        Mail::assertNothingSent();
    }

    /** Deux exécutions le même jour n'écrivent qu'une fois. */
    public function test_running_the_deadline_command_twice_writes_once(): void
    {
        Mail::fake();

        $this->abonnement(7);

        $this->artisan('subscriptions:notify');
        $this->artisan('subscriptions:notify');

        Mail::assertSent(SubscriptionExpiringMail::class, 1);
    }

    /**
     * L'ABONNEMENT ÉCHU EST CLOS EN BASE, pas seulement signalé.
     *
     * Sans cette écriture, il resterait « actif » indéfiniment : le client est
     * bien hors ligne, mais les compteurs de l'administration afficheraient
     * des abonnements actifs qui ne le sont plus. C'est ce chiffre-là qu'on
     * regarde pour décider.
     */
    public function test_an_expired_subscription_is_closed_and_announced(): void
    {
        Mail::fake();

        $abonnement = $this->abonnement(-2);

        $this->artisan('subscriptions:notify')->assertSuccessful();

        Mail::assertSent(SubscriptionExpiredMail::class);

        $this->assertSame(
            Subscription::STATUS_EXPIRED,
            $abonnement->refresh()->status,
            'Un abonnement échu doit être clos en base, sans quoi il ressortira demain.'
        );
    }

    /** Clos une fois, il ne réécrit plus jamais. */
    public function test_a_closed_subscription_never_writes_again(): void
    {
        Mail::fake();

        $this->abonnement(-2);

        $this->artisan('subscriptions:notify');
        $this->artisan('subscriptions:notify');

        Mail::assertSent(SubscriptionExpiredMail::class, 1);
    }
}
