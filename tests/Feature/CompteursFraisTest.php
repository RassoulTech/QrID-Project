<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\ProfileStatDaily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * LES COMPTEURS RELUS SANS RECHARGER LA PAGE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE CAS RÉEL QU'ILS SERVENT
 * ═══════════════════════════════════════════════════════════════════════════
 * Le client publie sa carte, prend son téléphone, scanne son propre QR Code
 * pour vérifier — puis revient sur son ordinateur. Sans relecture, il doit
 * recharger la page, et beaucoup concluent que rien n'a été compté.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CETTE ROUTE NE DOIT JAMAIS RENDRE
 * ═══════════════════════════════════════════════════════════════════════════
 * Trois entiers. Une réponse JSON traîne dans le cache du navigateur et dans
 * les outils de développement bien plus longtemps qu'une page : y glisser un
 * nom, une adresse ou un identifiant serait les y laisser.
 */
class CompteursFraisTest extends TestCase
{
    use RefreshDatabase;

    private function clientAvecCarte(): array
    {
        $client = User::factory()->create(['email_verified_at' => now()]);
        $carte = Profile::factory()->create(['user_id' => $client->id]);

        return [$client, $carte];
    }

    public function test_the_counters_reflect_what_was_recorded(): void
    {
        [$client, $carte] = $this->clientAvecCarte();

        ProfileEvent::factory()->count(3)->view()->create(['profile_id' => $carte->id]);
        ProfileEvent::factory()->count(2)->scan()->create(['profile_id' => $carte->id]);

        $this->actingAs($client)
            ->getJson(route('dashboard.compteurs'))
            ->assertOk()
            ->assertJsonPath('compteurs.views', 3)
            ->assertJsonPath('compteurs.scans', 2)
            ->assertJsonPath('compteurs.saves', 0);
    }

    /**
     * LES AGRÉGATS ET LA SOURCE S'ADDITIONNENT, SANS DOUBLON.
     *
     * C'est la même frontière que partout ailleurs : agrégats jusqu'au
     * dernier jour traité, source au-delà. Une erreur ici compterait deux
     * fois une journée dont les événements bruts ne sont pas encore purgés.
     */
    public function test_aggregated_history_and_today_add_up_once(): void
    {
        [$client, $carte] = $this->clientAvecCarte();

        ProfileStatDaily::create([
            'profile_id' => $carte->id,
            'jour' => Carbon::yesterday()->toDateString(),
            'vues' => 10, 'scans' => 4, 'saves' => 1, 'partages' => 0, 'total' => 15,
        ]);

        // Les bruts de la veille survivent à leur agrégation : ils ne sont
        // purgés qu'au-delà de la rétention.
        ProfileEvent::factory()->count(10)->view()->create([
            'profile_id' => $carte->id,
            'created_at' => Carbon::yesterday()->addHours(9),
        ]);

        ProfileEvent::factory()->count(2)->view()->create([
            'profile_id' => $carte->id,
            'created_at' => Carbon::today()->addHours(9),
        ]);

        $this->actingAs($client)
            ->getJson(route('dashboard.compteurs'))
            ->assertOk()
            ->assertJsonPath('compteurs.views', 12);
    }

    /** Sans carte, il n'y a rien à compter — et on le dit. */
    public function test_an_account_without_a_card_gets_nothing_to_show(): void
    {
        $client = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($client)
            ->getJson(route('dashboard.compteurs'))
            ->assertOk()
            ->assertJsonPath('compteurs', null);
    }

    /**
     * LA RÉPONSE NE PORTE AUCUNE DONNÉE NOMINATIVE.
     *
     * Trois entiers, et rien d'autre. Un nom ou un identifiant glissé ici
     * resterait dans le cache du navigateur.
     */
    public function test_the_response_carries_nothing_but_numbers(): void
    {
        [$client, $carte] = $this->clientAvecCarte();

        $corps = $this->actingAs($client)
            ->getJson(route('dashboard.compteurs'))
            ->getContent();

        $this->assertStringNotContainsString($client->email, $corps);
        $this->assertStringNotContainsString($carte->slug, $corps);
        $this->assertStringNotContainsString($carte->first_name, $corps);

        $this->assertSame(
            ['views', 'scans', 'saves'],
            array_keys(json_decode($corps, true)['compteurs']),
            'La réponse porte des clés qui ne sont pas des compteurs.',
        );
    }

    /** Un visiteur non connecté n'y accède pas. */
    public function test_a_guest_is_turned_away(): void
    {
        $this->getJson(route('dashboard.compteurs'))->assertUnauthorized();
    }

    /**
     * LE CLOISONNEMENT VAUT AUSSI ICI.
     *
     * Les compteurs viennent de la carte du compte CONNECTÉ, jamais d'un
     * identifiant reçu de l'extérieur — c'est ce qui rend l'usurpation
     * impossible sans qu'aucune vérification n'ait à être écrite.
     */
    public function test_the_counters_never_include_another_card(): void
    {
        [$client, $carte] = $this->clientAvecCarte();
        $voisine = Profile::factory()->create();

        ProfileEvent::factory()->count(7)->view()->create(['profile_id' => $voisine->id]);
        ProfileEvent::factory()->view()->create(['profile_id' => $carte->id]);

        $this->actingAs($client)
            ->getJson(route('dashboard.compteurs'))
            ->assertJsonPath('compteurs.views', 1);
    }
}
