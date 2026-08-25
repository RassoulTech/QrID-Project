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
 * L'AGRÉGATION DES STATISTIQUES, ET CE QU'ELLE GARANTIT.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS PROTÈGENT
 * ═══════════════════════════════════════════════════════════════════════
 * La page de statistiques de l'administration agrégeait `profile_events` en
 * entier. Mesuré sur 1 000 profils : 45 ms à 2 000 événements, 450 ms à
 * 100 000, et 32 301 ms à un million. Fois dix sur le volume, fois
 * soixante-douze sur le temps.
 *
 * La table d'agrégats supprime ce coût. Mais elle introduit un risque
 * nouveau, et c'est celui-ci qu'il faut verrouiller : un agrégat FAUX est
 * pire qu'un agrégat lent. Personne ne remarque un chiffre légèrement
 * décalé, et il n'existe plus de source pour le contredire une fois les
 * événements bruts purgés.
 */
class AgregationStatistiquesTest extends TestCase
{
    use RefreshDatabase;

    private function profil(): Profile
    {
        return Profile::factory()->create();
    }

    private function evenement(Profile $p, string $type, Carbon $quand): void
    {
        ProfileEvent::factory()->create([
            'profile_id' => $p->id,
            'type' => $type,
            'created_at' => $quand,
        ]);
    }

    /**
     * L'AGRÉGATION EST REJOUABLE, et c'est sa propriété la plus importante.
     *
     * Une nuit manquée, un conteneur redémarré au mauvais moment, une reprise
     * manuelle : tout cela arrive. Si repasser sur le même jour doublait les
     * compteurs, personne ne saurait dire quel chiffre est juste — et il n'y
     * aurait aucun moyen de le découvrir autrement qu'en recomptant à la main.
     */
    public function test_running_it_twice_changes_nothing(): void
    {
        $profil = $this->profil();
        $hier = Carbon::yesterday()->setHour(10);

        $this->evenement($profil, ProfileEvent::TYPE_VIEW, $hier);
        $this->evenement($profil, ProfileEvent::TYPE_VIEW, $hier);
        $this->evenement($profil, ProfileEvent::TYPE_SCAN, $hier);

        $this->artisan('app:agreger-statistiques')->assertSuccessful();
        $premier = ProfileStatDaily::where('profile_id', $profil->id)->firstOrFail();

        $this->artisan('app:agreger-statistiques')->assertSuccessful();
        $second = ProfileStatDaily::where('profile_id', $profil->id)->firstOrFail();

        $this->assertSame(1, ProfileStatDaily::count(), 'Une seconde ligne a été créée pour le même jour.');
        $this->assertSame(2, $second->vues);
        $this->assertSame(1, $second->scans);
        $this->assertSame(3, $second->total);
        $this->assertEquals($premier->total, $second->total, 'Les compteurs ont doublé à la seconde exécution.');
    }

    /**
     * LE JOUR EN COURS N'EST JAMAIS AGRÉGÉ.
     *
     * Il n'est pas terminé. Un agrégat partiel réécrit chaque heure vaudrait
     * moins qu'une lecture directe, et ferait afficher un chiffre qui recule
     * quand la journée avance.
     */
    public function test_today_is_left_out_of_the_aggregates(): void
    {
        $profil = $this->profil();

        $this->evenement($profil, ProfileEvent::TYPE_VIEW, Carbon::now()->setHour(9));

        $this->artisan('app:agreger-statistiques')->assertSuccessful();

        $this->assertSame(0, ProfileStatDaily::count(), "Le jour en cours a été agrégé alors qu'il n'est pas fini.");
    }

    /**
     * LA LECTURE ADDITIONNE L'HISTOIRE ET LA JOURNÉE EN COURS.
     *
     * C'est le point où une erreur passerait le plus facilement inaperçue :
     * afficher les seuls agrégats donnerait une page qui ignore ce qui s'est
     * passé depuis minuit, et le premier client qui scanne sa propre carte
     * pour vérifier verrait zéro.
     */
    public function test_reading_adds_today_to_the_history(): void
    {
        $profil = $this->profil();

        $this->evenement($profil, ProfileEvent::TYPE_VIEW, Carbon::yesterday()->setHour(10));
        $this->evenement($profil, ProfileEvent::TYPE_VIEW, Carbon::now()->setHour(9));

        $this->artisan('app:agreger-statistiques')->assertSuccessful();

        $totaux = app(StatistiquesLecture::class)->totaux(Carbon::today()->subDays(30));

        $this->assertSame(2, $totaux['vues'], "La journée en cours n'est pas comptée avec l'historique.");
        $this->assertSame(2, $totaux['total']);
    }

    /** Le classement lit les agrégats et rend les profils les plus vus. */
    public function test_the_ranking_reads_the_aggregates(): void
    {
        $discret = $this->profil();
        $populaire = $this->profil();
        $hier = Carbon::yesterday()->setHour(10);

        $this->evenement($discret, ProfileEvent::TYPE_VIEW, $hier);

        for ($i = 0; $i < 5; $i++) {
            $this->evenement($populaire, ProfileEvent::TYPE_VIEW, $hier);
        }

        $this->artisan('app:agreger-statistiques')->assertSuccessful();

        $classement = app(StatistiquesLecture::class)->classement(Carbon::today()->subDays(30));

        $this->assertCount(2, $classement);
        $this->assertSame($populaire->id, $classement->first()->id, 'Le classement ne trie pas par total décroissant.');
        $this->assertSame(5, $classement->first()->total);
    }

    /**
     * LA PURGE NE TOUCHE QUE CE QUI EST HORS RÉTENTION.
     *
     * Elle s'exécute derrière l'agrégation : supprimer une source qu'on n'a
     * pas encore résumée effacerait des chiffres pour toujours.
     */
    public function test_the_purge_spares_what_is_inside_the_retention(): void
    {
        config(['statistiques.retention_mois' => 12]);

        $profil = $this->profil();

        $this->evenement($profil, ProfileEvent::TYPE_VIEW, Carbon::today()->subMonths(18));
        $this->evenement($profil, ProfileEvent::TYPE_VIEW, Carbon::yesterday()->setHour(10));

        $this->artisan('app:agreger-statistiques --jours=600 --purger')->assertSuccessful();

        $this->assertSame(1, ProfileEvent::count(), 'La purge a emporté un événement encore dans la fenêtre.');

        // L'agrégat du vieil événement SURVIT à la purge de sa source : c'est
        // tout l'intérêt de résumer avant de supprimer.
        $this->assertSame(2, ProfileStatDaily::count());
    }
}
