<?php

namespace Tests\Feature;

use App\Support\Planificateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

/**
 * DES TÂCHES QUI RATTRAPENT.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════════
 * `dailyAt('02:30')` ne part que si le planificateur est interrogé PENDANT la
 * minute 02:30. Sur le plan gratuit de Render, le conteneur dort dès qu'il
 * n'y a plus de visiteurs : cette minute passe sans témoin, et la tâche n'est
 * pas retardée — elle est sautée, sans erreur ni trace.
 *
 * Les tâches demandent donc désormais « ai-je tourné aujourd'hui, et l'heure
 * est-elle passée ? ». Deux erreurs symétriques restent possibles, et les
 * deux sont silencieuses :
 *
 *   JAMAIS   la condition ne devient jamais vraie, et la tâche ne part plus.
 *   TROP     la condition reste vraie après l'exécution, et la tâche repart
 *            toutes les cinq minutes — 288 récapitulatifs dans la journée.
 */
class PlanificateurTest extends TestCase
{
    use RefreshDatabase;

    private const TACHE = 'app:agreger-statistiques';

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Le matin avant l'heure dite, il n'y a rien à faire. */
    public function test_a_task_is_not_due_before_its_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 01:00', Planificateur::FUSEAU));

        $this->assertFalse(Planificateur::estDueAujourdhui(self::TACHE, '02:30'));
    }

    /** L'heure passée et rien de fait : elle part. */
    public function test_a_task_becomes_due_once_its_hour_has_passed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 02:31', Planificateur::FUSEAU));

        $this->assertTrue(Planificateur::estDueAujourdhui(self::TACHE, '02:30'));
    }

    /**
     * LE RATTRAPAGE — le cœur du dispositif.
     *
     * Le conteneur a dormi toute la nuit et ne se réveille qu'à 14 h, au
     * premier visiteur. Avec `dailyAt`, la tâche de 02:30 serait perdue pour
     * la journée. Ici elle part au réveil.
     */
    public function test_a_task_still_runs_when_the_container_slept_through_its_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 14:07', Planificateur::FUSEAU));

        $this->assertTrue(Planificateur::estDueAujourdhui(self::TACHE, '02:30'),
            'Une tâche dont l\'heure est passée depuis longtemps doit rattraper, '.
            'sinon un conteneur endormi la fait sauter en silence.');
    }

    /** Une fois tentée, elle ne repart pas de la journée. */
    public function test_a_task_does_not_run_twice_in_the_same_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 02:31', Planificateur::FUSEAU));
        Planificateur::marquer(self::TACHE);

        $this->assertFalse(Planificateur::estDueAujourdhui(self::TACHE, '02:30'));

        // Cinq minutes plus tard, le planificateur repasse : toujours non.
        Carbon::setTestNow(Carbon::parse('2026-09-02 02:36', Planificateur::FUSEAU));
        $this->assertFalse(Planificateur::estDueAujourdhui(self::TACHE, '02:30'),
            'La tâche repart toutes les cinq minutes : le marqueur n\'est pas lu.');

        // Et le soir non plus.
        Carbon::setTestNow(Carbon::parse('2026-09-02 23:50', Planificateur::FUSEAU));
        $this->assertFalse(Planificateur::estDueAujourdhui(self::TACHE, '02:30'));
    }

    /** Le lendemain, elle repart. */
    public function test_a_task_runs_again_the_next_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 02:31', Planificateur::FUSEAU));
        Planificateur::marquer(self::TACHE);

        Carbon::setTestNow(Carbon::parse('2026-09-03 02:31', Planificateur::FUSEAU));

        $this->assertTrue(Planificateur::estDueAujourdhui(self::TACHE, '02:30'));
    }

    /**
     * L'échec ne provoque pas de salve.
     *
     * Le marqueur est posé après la TENTATIVE, pas après le succès : une
     * commande qui échoue attend le lendemain plutôt que de repartir 288 fois.
     */
    public function test_a_failed_task_waits_for_tomorrow_rather_than_retrying_all_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 09:00', Planificateur::FUSEAU));

        // `after()` marque quelle que soit l'issue : on simule cette pose.
        Planificateur::marquer('profiles:remind');

        Carbon::setTestNow(Carbon::parse('2026-09-02 09:05', Planificateur::FUSEAU));

        $this->assertFalse(Planificateur::estDueAujourdhui('profiles:remind', '09:00'));
    }

    /** L'hebdomadaire attend sept jours, pas un de moins. */
    public function test_a_weekly_task_waits_seven_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 04:30', Planificateur::FUSEAU));

        $this->assertTrue(Planificateur::estDueCetteSemaine('app:sauvegarder', '04:00'),
            'Jamais exécutée : elle doit partir.');

        Planificateur::marquer('app:sauvegarder');

        Carbon::setTestNow(Carbon::parse('2026-09-08 04:30', Planificateur::FUSEAU));
        $this->assertFalse(Planificateur::estDueCetteSemaine('app:sauvegarder', '04:00'),
            'Six jours seulement : trop tôt.');

        Carbon::setTestNow(Carbon::parse('2026-09-09 04:30', Planificateur::FUSEAU));
        $this->assertTrue(Planificateur::estDueCetteSemaine('app:sauvegarder', '04:00'),
            'Sept jours écoulés : elle repart.');
    }

    /**
     * DEUX APPELANTS SIMULTANÉS, UNE SEULE EXÉCUTION.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CE CAS EST RÉEL ET NON THÉORIQUE
     * ═══════════════════════════════════════════════════════════════════
     * Deux choses appellent le planificateur :
     *
     *   · `schedule:work`, dans le conteneur, chaque minute ;
     *   · la route /automation/schedule, appelée de l'extérieur — elle
     *     existait avant ce dispositif et reste un filet le jour où le
     *     conteneur dort.
     *
     * Si les deux passent dans la même minute, « lire puis écrire »
     * laisserait les deux partir : chacun lit « pas encore fait ». Deux
     * récapitulatifs Discord de la même journée, ou deux salves de relances
     * aux mêmes clients.
     *
     * La réclamation est donc un seul UPDATE conditionnel : le premier
     * obtient une ligne modifiée, le second zéro.
     */
    public function test_two_callers_in_the_same_minute_yield_one_run(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 21:00', Planificateur::FUSEAU));

        $premier = Planificateur::reclamerAujourdhui('report:daily', '21:00');
        $second = Planificateur::reclamerAujourdhui('report:daily', '21:00');

        $this->assertTrue($premier, 'Le premier appelant doit obtenir la journée.');
        $this->assertFalse($second,
            'Le second appelant a lui aussi obtenu la journée : le récapitulatif '.
            'partirait deux fois.');
    }

    /** La réclamation ne part pas avant l'heure, même concurrente. */
    public function test_claiming_before_the_hour_grants_nothing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 20:59', Planificateur::FUSEAU));

        $this->assertFalse(Planificateur::reclamerAujourdhui('report:daily', '21:00'));
        $this->assertNull(Planificateur::dernierJour('report:daily'),
            'Une réclamation refusée ne doit rien écrire : sinon la tâche serait '.
            'marquée faite sans avoir tourné.');
    }

    /** L'hebdomadaire se réclame aussi une seule fois. */
    public function test_a_weekly_task_is_claimed_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 04:30', Planificateur::FUSEAU));

        $this->assertTrue(Planificateur::reclamerCetteSemaine('app:sauvegarder', '04:00'));
        $this->assertFalse(Planificateur::reclamerCetteSemaine('app:sauvegarder', '04:00'));

        Carbon::setTestNow(Carbon::parse('2026-09-09 04:30', Planificateur::FUSEAU));
        $this->assertTrue(Planificateur::reclamerCetteSemaine('app:sauvegarder', '04:00'));
    }

    /**
     * LE BATTEMENT DE CŒUR — sans lui, un planificateur arrêté ressemble à un
     * planificateur qui n'a rien à faire.
     */
    public function test_the_heartbeat_reports_how_long_since_the_last_pass(): void
    {
        $this->assertNull(Planificateur::minutesDepuisLeDernierBattement(),
            'Jamais battu ne doit pas se lire comme « vient de battre ».');

        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00', Planificateur::FUSEAU));
        Planificateur::battre();

        Carbon::setTestNow(Carbon::parse('2026-09-02 10:07', Planificateur::FUSEAU));

        $this->assertSame(7, Planificateur::minutesDepuisLeDernierBattement());
    }

    /**
     * LA TABLE NE GROSSIT PAS.
     *
     * C'est un signet, pas un journal. Une ligne par tâche, mise à jour —
     * jamais ajoutée. Une insertion à chaque passage ferait 105 000 lignes
     * par an pour une information qui en tient huit.
     */
    public function test_the_marker_table_keeps_one_row_per_task(): void
    {
        foreach (['2026-09-02', '2026-09-03', '2026-09-04'] as $jour) {
            Carbon::setTestNow(Carbon::parse($jour.' 02:31', Planificateur::FUSEAU));
            Planificateur::marquer(self::TACHE);
        }

        $this->assertSame(1, \DB::table('taches_planifiees')->where('cle', self::TACHE)->count());
        $this->assertSame('2026-09-04', Planificateur::dernierJour(self::TACHE));
    }

    /**
     * Toutes les tâches quotidiennes sont bien déclarées au planificateur,
     * et aucune n'est restée sur l'ancien contrat `dailyAt`.
     */
    public function test_no_task_still_depends_on_being_asked_at_an_exact_minute(): void
    {
        $quotidiennes = collect(Schedule::events())
            ->map(fn ($e) => $e->expression)
            // Le moniteur de file est le seul à devoir passer chaque minute :
            // il ne fait que lire une profondeur, il n'a rien à rattraper.
            ->reject(fn ($expression) => $expression === '* * * * *');

        $this->assertNotEmpty($quotidiennes);

        foreach ($quotidiennes as $expression) {
            $this->assertSame('*/5 * * * *', $expression,
                "Une tâche est déclarée « {$expression} » : elle ne partira que si ".
                'le planificateur est interrogé à cette minute précise, ce qu\'un '.
                'conteneur endormi ne garantit pas.');
        }
    }
}
