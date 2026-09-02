<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\ProfileStatDaily;
use App\Services\StatistiquesLecture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * LA FRONTIÈRE ENTRE L'AGRÉGAT ET LA SOURCE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════════
 * Les statistiques se lisent à deux endroits : une table de compteurs
 * journaliers remplie par une tâche planifiée, et la table des événements
 * bruts. Toute la difficulté tient en une phrase : il faut lire chaque
 * journée UNE FOIS ET UNE SEULE, dans la bonne table.
 *
 * Deux pannes symétriques guettent :
 *
 *   TROP PEU  la tâche n'a pas tourné, on ne lit que les agrégats, et
 *             l'historique du client DISPARAÎT de son écran sans erreur.
 *
 *   TROP      la tâche a tourné mais les événements bruts sont encore là
 *             (ils ne sont purgés qu'au-delà de la rétention), on lit les
 *             deux tables, et chaque visite est comptée DEUX FOIS.
 *
 * Aucune des deux ne lève d'exception. Aucune ne casse un écran. Toutes
 * deux affichent simplement des chiffres faux — c'est-à-dire la panne la
 * plus coûteuse, celle qu'on ne remarque pas.
 */
class StatistiquesLectureTest extends TestCase
{
    use RefreshDatabase;

    private StatistiquesLecture $lecture;

    private Profile $profil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lecture = app(StatistiquesLecture::class);
        $this->profil = Profile::factory()->create();
    }

    /**
     * LE JOUR EN COURS N'EST JAMAIS AGRÉGÉ — il vient toujours de la source.
     *
     * Sans cela, le client qui scanne sa propre carte pour vérifier qu'elle
     * fonctionne verrait zéro, et en conclurait que le produit ne compte pas.
     */
    public function test_todays_events_are_counted_before_any_aggregation(): void
    {
        ProfileEvent::factory()->count(3)->view()->create([
            'profile_id' => $this->profil->id,
            'created_at' => Carbon::today()->addHours(10),
        ]);

        $totaux = $this->lecture->totaux(Carbon::today()->subDays(6), $this->profil->id);

        $this->assertSame(3, $totaux['vues']);
        $this->assertSame(3, $totaux['total']);
    }

    /**
     * LA PANNE « TROP PEU » — la tâche d'agrégation n'a jamais tourné.
     *
     * C'est l'état RÉEL de la production tant qu'aucun planificateur
     * n'existe. Les chiffres doivent rester justes, quitte à être lus
     * lentement : se dégrader en lenteur est acceptable, se dégrader en
     * mensonge ne l'est pas.
     */
    public function test_history_survives_when_aggregation_never_ran(): void
    {
        ProfileEvent::factory()->count(5)->view()->create([
            'profile_id' => $this->profil->id,
            'created_at' => Carbon::today()->subDays(3)->addHours(9),
        ]);

        $this->assertSame(0, ProfileStatDaily::count(),
            'Ce test décrit précisément le cas où rien n\'a été agrégé.');

        $totaux = $this->lecture->totaux(Carbon::today()->subDays(6), $this->profil->id);

        $this->assertSame(5, $totaux['vues'],
            "L'historique a disparu parce que la tâche d'agrégation n'a pas ".
            'tourné. Le service doit relire la source pour la portion non agrégée.');
    }

    /**
     * LA PANNE « TROP » — la journée est agrégée ET ses événements bruts
     * sont encore là. Elle ne doit être comptée qu'une fois.
     */
    public function test_an_aggregated_day_is_never_counted_twice(): void
    {
        $jour = Carbon::today()->subDays(2);

        // Les événements bruts survivent à leur agrégation : ils ne sont
        // purgés qu'au-delà de la rétention, des semaines plus tard.
        ProfileEvent::factory()->count(4)->view()->create([
            'profile_id' => $this->profil->id,
            'created_at' => $jour->copy()->addHours(11),
        ]);

        ProfileStatDaily::create([
            'profile_id' => $this->profil->id,
            'jour' => $jour->toDateString(),
            'vues' => 4, 'scans' => 0, 'saves' => 0, 'total' => 4,
        ]);

        // L'agrégation est allée jusqu'à hier : le jour ci-dessus est couvert.
        ProfileStatDaily::create([
            'profile_id' => $this->profil->id,
            'jour' => Carbon::yesterday()->toDateString(),
            'vues' => 0, 'scans' => 0, 'saves' => 0, 'total' => 0,
        ]);

        $totaux = $this->lecture->totaux(Carbon::today()->subDays(6), $this->profil->id);

        $this->assertSame(4, $totaux['vues'],
            'La journée a été comptée deux fois : une fois dans les agrégats, '.
            'une fois dans les événements bruts qui n\'ont pas encore été purgés.');
    }

    /**
     * L'AGRÉGATION S'EST ARRÊTÉE EN COURS DE ROUTE.
     *
     * Le cas réel d'une nuit ratée : tout ce qui précède la panne vient des
     * agrégats, tout ce qui suit vient de la source, et rien n'est perdu ni
     * compté deux fois.
     */
    public function test_the_gap_left_by_a_missed_night_is_read_from_the_source(): void
    {
        // Agrégé : J-5, deux vues.
        ProfileStatDaily::create([
            'profile_id' => $this->profil->id,
            'jour' => Carbon::today()->subDays(5)->toDateString(),
            'vues' => 2, 'scans' => 0, 'saves' => 0, 'total' => 2,
        ]);

        // NON agrégé : J-2, trois vues, encore à l'état brut.
        ProfileEvent::factory()->count(3)->view()->create([
            'profile_id' => $this->profil->id,
            'created_at' => Carbon::today()->subDays(2)->addHours(14),
        ]);

        // Et aujourd'hui, une de plus.
        ProfileEvent::factory()->view()->create([
            'profile_id' => $this->profil->id,
            'created_at' => Carbon::today()->addHours(8),
        ]);

        $totaux = $this->lecture->totaux(Carbon::today()->subDays(6), $this->profil->id);

        $this->assertSame(6, $totaux['vues'],
            'Attendu 2 (agrégé) + 3 (trou relu dans la source) + 1 (aujourd\'hui).');
    }

    /**
     * LES TOTAUX SONT LA SOMME DE LA SÉRIE — toujours, sans exception.
     *
     * Ce sont les deux chiffres que le client voit côte à côte sur le même
     * écran : la tuile « vues » et la hauteur cumulée des barres. Qu'ils se
     * contredisent est le genre de défaut qui détruit la confiance dans
     * TOUS les autres chiffres de la page.
     */
    public function test_the_totals_always_equal_the_sum_of_the_series(): void
    {
        ProfileStatDaily::create([
            'profile_id' => $this->profil->id,
            'jour' => Carbon::today()->subDays(4)->toDateString(),
            'vues' => 7, 'scans' => 2, 'saves' => 1, 'total' => 10,
        ]);

        ProfileEvent::factory()->count(2)->scan()->create([
            'profile_id' => $this->profil->id,
            'created_at' => Carbon::today()->addHours(9),
        ]);

        $depuis = Carbon::today()->subDays(6);

        $totaux = $this->lecture->totaux($depuis, $this->profil->id);
        $serie = $this->lecture->serieDetaillee($depuis, 7, $this->profil->id);

        $this->assertSame($totaux['vues'], (int) $serie->sum('vues'));
        $this->assertSame($totaux['scans'], (int) $serie->sum('scans'));
        $this->assertSame($totaux['total'], (int) $serie->sum('total'));
    }

    /**
     * La série rend un point par jour, du plus ancien à aujourd'hui, sans
     * trou : un jour creux vaut zéro et garde sa place, sinon les barres se
     * tassent et le graphique ment sur les creux.
     */
    public function test_the_series_has_one_point_per_day_including_empty_ones(): void
    {
        $serie = $this->lecture->serieDetaillee(Carbon::today()->subDays(6), 7, $this->profil->id);

        $this->assertCount(7, $serie);
        $this->assertSame(Carbon::today()->subDays(6)->toDateString(), $serie->keys()->first());
        $this->assertSame(Carbon::today()->toDateString(), $serie->keys()->last());
    }

    /**
     * Le cloisonnement vaut aussi pour les chiffres : la série d'un profil
     * ne contient jamais les visites d'un autre.
     */
    public function test_one_profiles_figures_never_include_anothers(): void
    {
        $voisin = Profile::factory()->create();

        ProfileEvent::factory()->count(9)->view()->create([
            'profile_id' => $voisin->id,
            'created_at' => Carbon::today()->addHours(10),
        ]);

        $totaux = $this->lecture->totaux(Carbon::today()->subDays(6), $this->profil->id);

        $this->assertSame(0, $totaux['total']);
    }
}
