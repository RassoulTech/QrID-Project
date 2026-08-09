<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * GARDE-FOU DES PERMISSIONS — le contrat de l'espace administrateur.
 *
 * Le test ne liste AUCUNE route à la main : il parcourt la table de routage
 * réelle et retient tout ce qui commence par `admin.`. Une route admin ajoutée
 * demain sans protection tombe ici sans que personne ait à penser à ce
 * fichier — c'est la seule forme de garde-fou qui survit à six mois.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{string, string, string}> [nom, méthode, uri]
     */
    private function routesAdmin(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $nom = $route->getName();

            if ($nom === null || ! str_starts_with($nom, 'admin.')) {
                continue;
            }

            // Une seule méthode par route suffit : HEAD double chaque GET.
            $methode = collect($route->methods())->first(fn ($m) => $m !== 'HEAD');

            $routes[] = [$nom, $methode, $route->uri()];
        }

        return $routes;
    }

    /**
     * Substitue les paramètres d'URL par des clés RÉELLES.
     *
     * Ce détail décide de ce que le test prouve. SubstituteBindings est en
     * queue du groupe `web`, donc AVANT `auth` et `admin` : un identifiant
     * inventé fait répondre 404 sans que la protection ait eu à se prononcer.
     * Le test passerait alors au vert en ne prouvant rien — un 404 n'est pas
     * un refus d'accès, c'est une absence de données.
     *
     * On crée donc de vraies lignes, et chaque modèle est désigné par SA clé
     * de route : slug pour les profils, modèles et formules, identifiant pour
     * les comptes et les paiements.
     */
    private function uriConcrete(string $uri, array $cles): string
    {
        foreach ($cles as $parametre => $valeur) {
            $uri = str_replace('{'.$parametre.'}', (string) $valeur, $uri);
        }

        return '/'.ltrim($uri, '/');
    }

    /** @return array<string, string|int> */
    private function clesReelles(): array
    {
        $client = User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]);
        $modele = Template::factory()->create();
        $plan = Plan::factory()->create();

        return [
            'user' => $client->id,
            'profile' => Profile::factory()->for($client)->for($modele)->create()->slug,
            'payment' => Payment::factory()->for($client)->create()->id,
            'template' => $modele->slug,
            'plan' => $plan->slug,
        ];
    }

    // =======================================================================
    // TOUTE ROUTE ADMIN EST PROTÉGÉE
    // =======================================================================

    public function test_every_admin_route_is_prefixed_and_guarded(): void
    {
        $fautes = [];

        foreach ($this->routesAdmin() as [$nom, , $uri]) {
            if (! str_starts_with($uri, 'admin')) {
                $fautes[] = "{$nom} n'est pas sous /admin ({$uri})";
            }

            $middlewares = Route::getRoutes()->getByName($nom)->gatherMiddleware();

            foreach (['auth', 'verified', 'admin'] as $requis) {
                if (! in_array($requis, $middlewares, true)) {
                    $fautes[] = "{$nom} n'a pas le middleware « {$requis} »";
                }
            }
        }

        $this->assertSame([], $fautes, "Routes admin mal protégées :\n".implode("\n", $fautes));
    }

    /**
     * Aucune closure : une route en closure échoue à route:cache, et la mise
     * en cache des routes est justement ce qu'on active en production.
     */
    public function test_no_admin_route_uses_a_closure(): void
    {
        $closures = [];

        foreach ($this->routesAdmin() as [$nom]) {
            $action = Route::getRoutes()->getByName($nom)->getAction();

            if (($action['uses'] ?? null) instanceof \Closure) {
                $closures[] = $nom;
            }
        }

        $this->assertSame([], $closures, 'Routes admin en closure : '.implode(', ', $closures));
    }

    // =======================================================================
    // 403 POUR UN COMPTE NON ADMINISTRATEUR
    // =======================================================================

    /**
     * 403, jamais un écran partiel ni une redirection silencieuse.
     *
     * Une redirection vers le tableau de bord donnerait au client
     * l'impression d'un lien cassé ; le 403 dit ce qu'il en est.
     */
    public function test_a_client_gets_403_on_every_admin_route(): void
    {
        $cles = $this->clesReelles();

        $client = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $ecarts = [];

        foreach ($this->routesAdmin() as [$nom, $methode, $uri]) {
            $code = $this->actingAs($client)
                ->call($methode, $this->uriConcrete($uri, $cles))
                ->getStatusCode();

            if ($code !== 403) {
                $ecarts[] = "{$nom} ({$methode} {$uri}) → {$code}";
            }
        }

        $this->assertSame([], $ecarts, "Routes admin accessibles à un client :\n".implode("\n", $ecarts));
    }

    /** Un invité est renvoyé vers la connexion, pas vers un 403 anonyme. */
    public function test_a_guest_is_sent_to_the_login_screen(): void
    {
        $this->get('/admin/vue-ensemble')->assertRedirect(route('login'));
        $this->get('/admin/clients')->assertRedirect(route('login'));
    }

    /**
     * Un compte non vérifié n'entre pas non plus. Le middleware `verified` est
     * dans la pile, ce test prouve qu'il agit — la liste des middlewares seule
     * ne dit pas qu'ils sont dans le bon ordre.
     */
    public function test_an_unverified_admin_is_turned_away(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => null,
        ]);

        $this->actingAs($admin)
            ->get('/admin/vue-ensemble')
            ->assertStatus(302);
    }

    // =======================================================================
    // TOUTES LES ROUTES SONT NOMMÉES
    // =======================================================================

    /**
     * Une route admin sans nom obligerait à écrire son URL en dur dans une
     * vue. C'est exactement ainsi que l'ancien menu pointait /systeme/etat,
     * une adresse qui n'existait plus.
     */
    public function test_every_route_under_admin_is_named(): void
    {
        $anonymes = [];

        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'admin') && $route->getName() === null) {
                $anonymes[] = $route->methods()[0].' '.$route->uri();
            }
        }

        $this->assertSame([], $anonymes, 'Routes admin sans nom : '.implode(', ', $anonymes));
    }
}
