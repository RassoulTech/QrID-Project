<?php

namespace Tests\Feature;

use App\Models\CardOrder;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * LE NOMBRE DE REQUÊTES NE DOIT PAS DÉPENDRE DU NOMBRE DE LIGNES.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LA PANNE QUE CE FICHIER ATTRAPE
 * ═══════════════════════════════════════════════════════════════════════════
 * Une boucle qui touche une relation non chargée produit une requête PAR
 * LIGNE. Sur les vingt lignes d'une page de test, personne ne le remarque :
 * la page répond en quarante millisecondes.
 *
 * Le défaut ne se voit qu'en production, quand la même page sert cinquante
 * lignes à un administrateur pressé — et il ne se voit alors PAS NON PLUS,
 * parce qu'une page à 1,2 seconde passe pour « un peu lente » plutôt que
 * pour un défaut. On l'attribue à l'hébergement, et on paie plus cher pour
 * un problème qui coûtait une ligne de `with()`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LA MÉTHODE : DOUBLER LES DONNÉES, PAS COMPTER LES REQUÊTES
 * ═══════════════════════════════════════════════════════════════════════════
 * Fixer un nombre de requêtes attendu — « cette page en fait douze » — donne
 * un test qui casse à chaque ajout légitime, et qu'on finit par mettre à jour
 * sans réfléchir. Il ne protège plus rien.
 *
 * On mesure donc la PENTE : la page est rendue avec peu de lignes, puis avec
 * beaucoup plus. Si le nombre de requêtes suit, il y a une boucle. S'il reste
 * stable, la relation est chargée d'avance — quel que soit le compte exact,
 * et quels que soient les ajouts futurs.
 */
class ChargeDesEcransTest extends TestCase
{
    use RefreshDatabase;

    /** Le nombre de requêtes exécutées pendant le rendu d'une page. */
    private function requetesPour(callable $rendu): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $rendu();

        $compte = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $compte;
    }

    private function administrateur(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /** Des clients complets : compte, abonnement, carte, paiement. */
    private function clients(int $combien): void
    {
        $plan = Plan::factory()->create(['slug' => 'standard-'.uniqid(), 'duration_days' => 30]);

        for ($i = 0; $i < $combien; $i++) {
            $client = User::factory()->create(['email_verified_at' => now()]);

            $abonnement = Subscription::factory()->create([
                'user_id' => $client->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'ends_at' => now()->addDays(20),
            ]);

            $profil = Profile::factory()->create([
                'user_id' => $client->id,
                'is_active' => true,
            ]);

            Payment::factory()->create([
                'user_id' => $client->id,
                'subscription_id' => $abonnement->id,
            ]);

            CardOrder::factory()->create(['profile_id' => $profil->id]);
        }
    }

    /**
     * Le rendu d'un écran coûte-t-il le même nombre de requêtes avec cinq
     * lignes qu'avec vingt ?
     */
    private function assertPenteConstante(string $route, string $ecran): void
    {
        $admin = $this->administrateur();

        $this->clients(5);
        $avecPeu = $this->requetesPour(fn () => $this->actingAs($admin)->get(route($route))->assertOk());

        $this->clients(15);
        $avecBeaucoup = $this->requetesPour(fn () => $this->actingAs($admin)->get(route($route))->assertOk());

        $this->assertLessThanOrEqual($avecPeu + 2, $avecBeaucoup,
            "L'écran « {$ecran} » exécute {$avecPeu} requêtes sur 5 lignes et ".
            "{$avecBeaucoup} sur 20 : une relation est chargée dans la boucle ".
            'plutôt qu\'en amont. La marge de 2 couvre les requêtes de comptage '.
            'dont le coût ne dépend pas du nombre de lignes.');
    }

    public function test_the_client_list_does_not_query_per_row(): void
    {
        $this->assertPenteConstante('admin.clients.index', 'Clients');
    }

    public function test_the_profiles_screen_does_not_query_per_row(): void
    {
        $this->assertPenteConstante('admin.profiles.index', 'Profils');
    }

    public function test_the_payments_screen_does_not_query_per_row(): void
    {
        $this->assertPenteConstante('admin.payments.index', 'Paiements');
    }

    public function test_the_card_orders_screen_does_not_query_per_row(): void
    {
        $this->assertPenteConstante('admin.cards.index', 'Commandes de cartes');
    }

    /**
     * ET LA PAGE PUBLIQUE — celle qui prend tout le trafic du produit.
     *
     * Elle ne dépend pas d'une liste : son coût doit être le MÊME quel que
     * soit le nombre d'événements déjà enregistrés sur la carte. Une lecture
     * qui grossit avec l'audience punit précisément les cartes qui marchent.
     */
    public function test_the_public_card_costs_the_same_however_popular_it_is(): void
    {
        $plan = Plan::factory()->create(['slug' => 'standard', 'duration_days' => 30]);
        $client = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);

        $carte = Profile::factory()->create(['user_id' => $client->id, 'is_active' => true]);

        /*
         | LE CACHE EST VIDÉ AVANT CHAQUE MESURE.
         |
         | Sans cela on comparerait un rendu froid à un rendu mémorisé, et le
         | test dirait que la page est devenue rapide — alors qu'il doit dire
         | tout autre chose : que son coût ne suit pas son audience. Ce sont
         | deux questions différentes, et mélanger les deux ferait passer une
         | régression pour une amélioration.
         */
        ProfileEvent::factory()->count(5)->view()->create(['profile_id' => $carte->id]);
        Cache::flush();
        $discrete = $this->requetesPour(fn () => $this->get(route('profile.public', $carte->slug))->assertOk());

        ProfileEvent::factory()->count(200)->view()->create(['profile_id' => $carte->id]);
        Cache::flush();
        $populaire = $this->requetesPour(fn () => $this->get(route('profile.public', $carte->slug))->assertOk());

        $this->assertSame($discrete, $populaire,
            "La carte publique coûte {$discrete} requêtes avec 5 événements et ".
            "{$populaire} avec 205 : son coût suit son audience.");
    }
}
