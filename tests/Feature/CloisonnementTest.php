<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * LE CLOISONNEMENT DES DONNÉES — ce que le compte A ne doit jamais atteindre
 * chez le compte B.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI CE TEST NE LISTE AUCUNE ROUTE À LA MAIN
 * ═══════════════════════════════════════════════════════════════════════════
 * Le produit ne cloisonne pas par « policy » mais PAR PORTÉE DE REQUÊTE : les
 * données sont lues depuis l'utilisateur connecté (`$request->user()->alerts()`)
 * et non cherchées par identifiant. C'est plus sûr que des policies, parce
 * qu'on ne peut pas oublier d'autoriser un modèle qu'on ne récupère jamais
 * par son id.
 *
 * Il reste une exception, et c'est la seule surface à risque : les routes qui
 * reçoivent un modèle PAR L'URL. Celles-là dépendent d'une vérification écrite
 * à la main dans le contrôleur, et une vérification écrite à la main peut être
 * oubliée le jour où l'on ajoute une route.
 *
 * Ce fichier parcourt donc la table de routage RÉELLE. Une route client
 * paramétrée ajoutée demain sans garde tombe ici sans que personne ait eu à
 * penser à ce fichier.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LES ROUTES `admin.` SONT EXCLUES, ET C'EST VOULU
 * ═══════════════════════════════════════════════════════════════════════════
 * Un administrateur DOIT pouvoir atteindre la ressource de n'importe qui —
 * c'est sa fonction. Que seul un administrateur y parvienne est le contrat
 * d'AdminAccessTest, qui parcourt ces routes-là de la même façon.
 */
class CloisonnementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Comment fabriquer la ressource visée par chaque paramètre d'URL.
     *
     * La clé est le nom du paramètre tel qu'il apparaît dans `/chemin/{param}`.
     * Le test vérifie plus bas que cette table couvre TOUTE la surface : une
     * route paramétrée dont le paramètre manque ici fait échouer la suite,
     * plutôt que de passer inaperçue.
     *
     * @return array<string, \Closure(User): \Illuminate\Database\Eloquent\Model>
     */
    private function fabriques(): array
    {
        return [
            'payment' => fn (User $proprietaire) => Payment::factory()->create([
                'user_id' => $proprietaire->id,
                'subscription_id' => Subscription::factory()->create([
                    'user_id' => $proprietaire->id,
                ])->id,
            ]),

            'notification' => fn (User $proprietaire) => Notification::create([
                'user_id' => $proprietaire->id,
                'type' => 'info',
                'title' => 'Titre',
                'body' => 'Corps',
                'url' => route('dashboard'),
            ]),
        ];
    }

    /**
     * Les routes client (hors `admin.`) qui reçoivent un modèle par l'URL.
     *
     * @return list<array{string, string, string}> [nom, méthode, paramètre]
     */
    private function routesClientParametrees(): array
    {
        $trouvees = [];

        foreach (Route::getRoutes() as $route) {
            $nom = $route->getName();
            $intergiciels = implode(',', $route->gatherMiddleware());

            if ($nom === null || str_starts_with($nom, 'admin.')) {
                continue;
            }

            // Une route publique n'a rien à cloisonner : la page publique
            // d'une carte est faite pour être vue par des inconnus.
            if (! str_contains($intergiciels, 'auth')) {
                continue;
            }

            if (! preg_match('/\{(\w+)\??\}/', $route->uri(), $m)) {
                continue;
            }

            // HEAD double chaque GET et n'apporte aucune assertion.
            $methode = collect($route->methods())->first(fn ($x) => $x !== 'HEAD');

            $trouvees[] = [$nom, $methode, $m[1]];
        }

        return $trouvees;
    }

    /**
     * LA SURFACE EST-ELLE ENTIÈREMENT COUVERTE ?
     *
     * Ce test ne vérifie aucune permission : il vérifie que le test SUIVANT
     * a de quoi travailler. Sans lui, ajouter une route paramétrée dont le
     * paramètre est inconnu de `fabriques()` la ferait simplement ignorer —
     * et la suite resterait verte en ne testant rien.
     */
    public function test_every_client_route_with_a_parameter_is_covered(): void
    {
        $fabriques = $this->fabriques();
        $orphelines = [];

        foreach ($this->routesClientParametrees() as [$nom, , $parametre]) {
            if (! isset($fabriques[$parametre])) {
                $orphelines[] = $nom.' ({'.$parametre.'})';
            }
        }

        $this->assertSame([], $orphelines,
            "Ces routes client reçoivent un modèle par l'URL et ne sont pas ".
            "couvertes par le test de cloisonnement. Ajoutez leur paramètre ".
            "à fabriques()."
        );
    }

    /**
     * LE CŒUR : la ressource de A, demandée par B, doit être refusée.
     */
    public function test_a_client_cannot_reach_another_clients_resource(): void
    {
        $fabriques = $this->fabriques();

        foreach ($this->routesClientParametrees() as [$nom, $methode, $parametre]) {
            $proprietaire = $this->clientVerifie();
            $intrus = $this->clientVerifie();

            $ressource = $fabriques[$parametre]($proprietaire);

            $reponse = $this->actingAs($intrus)
                ->call($methode, route($nom, [$parametre => $ressource]));

            $this->assertContains($reponse->getStatusCode(), [403, 404],
                "La route « {$nom} » a laissé un compte tiers atteindre la ".
                "ressource d'un autre (statut {$reponse->getStatusCode()}). ".
                "Il manque une vérification de propriété dans le contrôleur."
            );
        }
    }

    /**
     * LE CONTRE-TEST, ET IL EST INDISPENSABLE.
     *
     * Un test qui n'attend que des 403 reste vert si la route est cassée, si
     * elle renvoie 403 à tout le monde, ou si le garde est trop large. Il
     * faut donc prouver que le refus DISCRIMINE : le propriétaire, lui, passe.
     */
    public function test_a_client_still_reaches_their_own_resource(): void
    {
        $fabriques = $this->fabriques();

        foreach ($this->routesClientParametrees() as [$nom, $methode, $parametre]) {
            $proprietaire = $this->clientVerifie();
            $ressource = $fabriques[$parametre]($proprietaire);

            $reponse = $this->actingAs($proprietaire)
                ->call($methode, route($nom, [$parametre => $ressource]));

            $this->assertNotContains($reponse->getStatusCode(), [403, 404],
                "La route « {$nom} » refuse au propriétaire l'accès à sa ".
                "propre ressource (statut {$reponse->getStatusCode()}) : ".
                "le garde est trop large."
            );
        }
    }

    /**
     * Un visiteur non connecté n'atteint aucune de ces routes.
     */
    public function test_a_guest_reaches_none_of_them(): void
    {
        $fabriques = $this->fabriques();

        foreach ($this->routesClientParametrees() as [$nom, $methode, $parametre]) {
            $ressource = $fabriques[$parametre]($this->clientVerifie());

            $this->call($methode, route($nom, [$parametre => $ressource]))
                ->assertRedirect(route('login'));
        }
    }

    /** Un compte client ordinaire, e-mail vérifié — l'état nominal. */
    private function clientVerifie(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }
}
