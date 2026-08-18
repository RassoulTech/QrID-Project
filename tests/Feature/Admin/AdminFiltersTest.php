<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LES FILTRES DES LISTES D'ADMINISTRATION.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CES TESTS EXISTENT
 * ═══════════════════════════════════════════════════════════════════════
 * Le filtre par statut de la liste des paiements renvoyait TOUJOURS zéro
 * ligne, alors que l'écran proposait lui-même le lien et que la base
 * contenait neuf paiements réussis. La cause tenait en un caractère :
 *
 *     ->when($avecStatut && $request->query('statut'), fn ($q, string $s) => …)
 *
 * `when()` transmet au callback LA VALEUR DE SA CONDITION. Le callback
 * recevait donc `true`, que le typage `string` convertissait en « "1" » — et
 * la requête cherchait un statut nommé « 1 ».
 *
 * Rien ne levait d'erreur. La liste s'affichait, vide, avec l'aplomb d'une
 * liste réellement vide.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS VÉRIFIENT VRAIMENT
 * ═══════════════════════════════════════════════════════════════════════
 * Pas « la page répond 200 » — elle répondait 200. Mais que le filtre REND
 * LES BONNES LIGNES : celles qui portent le critère, et pas les autres. Un
 * filtre qui ne renvoie rien et un filtre qui renvoie tout sont deux façons
 * de ne pas filtrer.
 */
class AdminFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function client(string $nom, string $email): User
    {
        return User::factory()->create([
            'name' => $nom,
            'email' => $email,
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);
    }

    // =======================================================================
    // PAIEMENTS — le défaut d'origine
    // =======================================================================

    private function paiements(): void
    {
        $plan = Plan::factory()->create(['slug' => 'mensuel', 'price_fcfa' => 2500]);
        $client = $this->client('Awa Ndiaye', 'awa@exemple.sn');

        foreach ([
            ['REUSSI-1', Payment::STATUS_SUCCESS, 'wave'],
            ['REUSSI-2', Payment::STATUS_SUCCESS, 'orange_money'],
            ['ECHOUE-1', Payment::STATUS_FAILED, 'wave'],
            ['ATTENTE-1', Payment::STATUS_PENDING, 'free_money'],
        ] as [$ref, $statut, $moyen]) {
            Payment::factory()->create([
                'user_id' => $client->id,
                'provider_ref' => $ref,
                'status' => $statut,
                'method' => $moyen,
                'amount_fcfa' => $plan->price_fcfa,
            ]);
        }
    }

    /**
     * LE TEST QUI AURAIT ÉVITÉ LE DÉFAUT.
     *
     * Il ne se contente pas de compter : il vérifie que la référence d'un
     * paiement échoué N'EST PAS dans la page filtrée sur « réussi ». Compter
     * seul laisserait passer un filtre qui renvoie le bon NOMBRE de mauvaises
     * lignes.
     */
    public function test_the_payment_status_filter_returns_only_that_status(): void
    {
        $this->paiements();

        $reponse = $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['statut' => Payment::STATUS_SUCCESS]))
            ->assertOk();

        $reponse->assertSee('REUSSI-1')
            ->assertSee('REUSSI-2')
            ->assertDontSee('ECHOUE-1')
            ->assertDontSee('ATTENTE-1');
    }

    /** Et les deux autres statuts filtrent tout aussi bien. */
    public function test_the_failed_and_pending_filters_each_isolate_their_own(): void
    {
        $this->paiements();

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['statut' => Payment::STATUS_FAILED]))
            ->assertSee('ECHOUE-1')
            ->assertDontSee('REUSSI-1');

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['statut' => Payment::STATUS_PENDING]))
            ->assertSee('ATTENTE-1')
            ->assertDontSee('REUSSI-1');
    }

    /** Deux filtres se combinent en intersection, jamais en union. */
    public function test_status_and_method_combine_as_an_intersection(): void
    {
        $this->paiements();

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', [
                'statut' => Payment::STATUS_SUCCESS,
                'moyen' => 'wave',
            ]))
            ->assertSee('REUSSI-1')        // réussi ET wave
            ->assertDontSee('REUSSI-2')    // réussi mais orange_money
            ->assertDontSee('ECHOUE-1');   // wave mais échoué
    }

    // =======================================================================
    // CLIENTS
    // =======================================================================

    public function test_the_client_search_returns_only_matching_accounts(): void
    {
        $this->client('Awa Ndiaye', 'awa@exemple.sn');
        $this->client('Moussa Diop', 'moussa@exemple.sn');

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index', ['q' => 'Ndiaye']))
            ->assertOk()
            ->assertSee('awa@exemple.sn')
            ->assertDontSee('moussa@exemple.sn');
    }

    /** Un compte bloqué et un compte actif ne se mélangent pas. */
    public function test_the_client_status_filter_separates_blocked_accounts(): void
    {
        $this->client('Awa Ndiaye', 'awa@exemple.sn');
        $bloque = $this->client('Moussa Diop', 'moussa@exemple.sn');
        $bloque->forceFill(['is_blocked' => true])->save();

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index', ['statut' => 'bloque']))
            ->assertOk()
            ->assertSee('moussa@exemple.sn')
            ->assertDontSee('awa@exemple.sn');
    }

    // =======================================================================
    // PROFILS
    // =======================================================================

    public function test_the_profile_state_filter_separates_published_from_draft(): void
    {
        Template::factory()->create();

        $publie = $this->client('Awa Ndiaye', 'awa@exemple.sn');
        Profile::factory()->for($publie)->create(['slug' => 'awa-publiee', 'is_active' => true]);

        $brouillon = $this->client('Moussa Diop', 'moussa@exemple.sn');
        Profile::factory()->for($brouillon)->create(['slug' => 'moussa-brouillon', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.profiles.index', ['etat' => 'published']))
            ->assertOk()
            ->assertSee('awa-publiee')
            ->assertDontSee('moussa-brouillon');
    }

    // =======================================================================
    // ABONNEMENTS
    // =======================================================================

    public function test_the_subscription_plan_filter_returns_only_that_plan(): void
    {
        $mensuel = Plan::factory()->create(['slug' => 'mensuel', 'name' => 'Mensuel']);
        $annuel = Plan::factory()->create(['slug' => 'annuel', 'name' => 'Annuel']);

        $unClient = $this->client('Awa Ndiaye', 'awa@exemple.sn');
        $autre = $this->client('Moussa Diop', 'moussa@exemple.sn');

        Subscription::factory()->create([
            'user_id' => $unClient->id, 'plan_id' => $mensuel->id,
            'status' => Subscription::STATUS_ACTIVE, 'ends_at' => now()->addDays(20),
        ]);
        Subscription::factory()->create([
            'user_id' => $autre->id, 'plan_id' => $annuel->id,
            'status' => Subscription::STATUS_ACTIVE, 'ends_at' => now()->addDays(300),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.subscriptions.index', ['plan' => 'mensuel']))
            ->assertOk()
            ->assertSee('awa@exemple.sn')
            ->assertDontSee('moussa@exemple.sn');
    }

    // =======================================================================
    // LA PAGINATION — le piège classique
    // =======================================================================

    /**
     * LES FILTRES SURVIVENT AU CHANGEMENT DE PAGE.
     *
     * Sans `withQueryString()`, le lien « page 2 » repart sans les filtres :
     * on croit parcourir un résultat filtré, on parcourt la liste entière. Le
     * défaut est invisible sur la page 1, c'est-à-dire pendant tout le
     * développement.
     */
    public function test_filters_survive_pagination(): void
    {
        // De quoi dépasser les 15 lignes par page, tous bloqués : la page 2
        // n'existe QUE si le filtre est bien appliqué des deux côtés.
        foreach (range(1, 20) as $i) {
            $this->client("Bloque {$i}", "bloque{$i}@exemple.sn")
                ->forceFill(['is_blocked' => true])->save();
        }

        $this->client('Actif Unique', 'actif@exemple.sn');

        $page2 = $this->actingAs($this->admin)
            ->get(route('admin.clients.index', ['statut' => 'bloque', 'page' => 2]))
            ->assertOk();

        // Le compte actif ne doit apparaître sur AUCUNE page du résultat filtré.
        $page2->assertDontSee('actif@exemple.sn');

        // Et le lien de pagination porte bien le filtre.
        $this->actingAs($this->admin)
            ->get(route('admin.clients.index', ['statut' => 'bloque']))
            ->assertSee('statut=bloque', false);
    }

    // =======================================================================
    // CE QUE LA LISTE DIT D'ELLE-MÊME
    // =======================================================================

    /** Un filtre sans résultat le dit, et propose d'en sortir. */
    public function test_an_empty_result_offers_a_way_back(): void
    {
        $this->client('Awa Ndiaye', 'awa@exemple.sn');

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index', ['q' => 'introuvable-xyz']))
            ->assertOk()
            ->assertSee('Aucun résultat pour ces filtres', false)
            ->assertSee('Réinitialiser les filtres', false);
    }

    /**
     * Le compteur dit combien de résultats, pas combien de lignes affichées.
     *
     * Sans lui, quinze lignes peuvent aussi bien être quinze résultats que la
     * première page de deux cents — et l'on ne sait pas si le filtre a mordu.
     */
    public function test_the_counter_announces_the_filtered_total(): void
    {
        foreach (range(1, 3) as $i) {
            $this->client("Client {$i}", "client{$i}@exemple.sn");
        }

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('clients', false);

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index', ['q' => 'Client 1']))
            ->assertOk()
            ->assertSee('après filtrage', false);
    }
}
