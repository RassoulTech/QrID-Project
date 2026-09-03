<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * QUI PEUT ATTEINDRE QUOI — la matrice, vérifiée route par route.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER AJOUTE À CE QUI EXISTE DÉJÀ
 * ═══════════════════════════════════════════════════════════════════════════
 * Le produit couvrait déjà beaucoup : la matrice d'inscription en neuf cas,
 * celle de l'espace client à travers tout l'assistant, l'accès administrateur,
 * et le cloisonnement des ressources reçues par l'URL.
 *
 * Restait un trou, et c'est le plus élémentaire : personne ne vérifiait
 * systématiquement qu'un INVITÉ est refoulé de chaque route privée, ni qu'un
 * compte NON VÉRIFIÉ l'est des routes qui l'exigent.
 *
 * Ce sont des vérifications qu'on fait à la main en écrivant une route, donc
 * des vérifications qu'on oublie de faire en écrivant la trentième.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LA TROISIÈME ASSERTION EST LA PLUS UTILE
 * ═══════════════════════════════════════════════════════════════════════════
 * Vérifier qu'une route privée refuse un invité attrape l'oubli d'un
 * middleware. Vérifier qu'une route PUBLIQUE accueille un invité attrape
 * l'inverse — un `auth` posé par réflexe sur un groupe, qui rend d'un coup la
 * page d'accueil ou une carte inaccessible à qui n'a pas de compte.
 *
 * La seconde panne est plus grave que la première : elle coupe le produit de
 * ses visiteurs, et elle ne produit aucune erreur. Elle se découvre par un
 * client qui appelle pour dire que son lien ne marche plus.
 */
class MatriceDAccesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les routes GET sans paramètre — celles qu'on peut appeler telles quelles.
     *
     * Les routes paramétrées demandent une ressource à fabriquer : elles sont
     * couvertes par CloisonnementTest, qui sait la construire.
     *
     * @return list<array{string, string}> [nom, intergiciels]
     */
    private function routesSimples(): array
    {
        $trouvees = [];

        foreach (Route::getRoutes() as $route) {
            $nom = $route->getName();

            if ($nom === null || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            if (str_contains($route->uri(), '{')) {
                continue;
            }

            // Routes du framework et points d'automatisation : ils ont leur
            // propre protection, vérifiée ailleurs.
            if (str_starts_with($nom, 'storage.') || $nom === 'automation.schedule') {
                continue;
            }

            $trouvees[] = [$nom, implode(',', $route->gatherMiddleware())];
        }

        return $trouvees;
    }

    /**
     * UN INVITÉ NE FRANCHIT AUCUNE PORTE PRIVÉE.
     *
     * On n'exige pas une destination précise — `login` pour l'espace client,
     * autre chose peut-être demain pour l'administration. On exige qu'il ne
     * VOIE PAS la page : une redirection, ou un refus. Un 200 est la panne.
     */
    public function test_a_guest_never_reaches_a_private_page(): void
    {
        $ouvertes = [];

        foreach ($this->routesSimples() as [$nom, $intergiciels]) {
            if (! str_contains($intergiciels, 'auth')) {
                continue;
            }

            $statut = $this->get(route($nom))->getStatusCode();

            if ($statut === 200) {
                $ouvertes[] = $nom;
            }
        }

        $this->assertSame([], $ouvertes,
            'Ces routes portent le middleware « auth » et se sont pourtant '.
            "affichées pour un visiteur non connecté :\n  - ".implode("\n  - ", $ouvertes));
    }

    /**
     * UN COMPTE NON VÉRIFIÉ N'ATTEINT PAS CE QUI EXIGE UNE VÉRIFICATION.
     *
     * C'est la garantie qui empêche quelqu'un d'utiliser le produit avec une
     * adresse qu'il ne possède pas — et donc de recevoir sur cette adresse
     * les liens de réinitialisation d'un compte qui n'est pas le sien.
     */
    public function test_an_unverified_account_stops_at_the_verification_wall(): void
    {
        $client = User::factory()->create(['email_verified_at' => null]);
        $ouvertes = [];

        foreach ($this->routesSimples() as [$nom, $intergiciels]) {
            if (! str_contains($intergiciels, 'verified')) {
                continue;
            }

            $statut = $this->actingAs($client)->get(route($nom))->getStatusCode();

            if ($statut === 200) {
                $ouvertes[] = $nom;
            }
        }

        $this->assertSame([], $ouvertes,
            'Ces routes exigent un e-mail vérifié et se sont pourtant affichées '.
            "pour un compte qui ne l'a pas :\n  - ".implode("\n  - ", $ouvertes));
    }

    /**
     * LES PORTES PUBLIQUES QUI RENVOIENT LÉGITIMEMENT VERS LA CONNEXION.
     *
     * Chaque entrée est une décision, vérifiée en lisant le contrôleur.
     *
     * @return array<string, string>
     */
    private function exemptionsPubliques(): array
    {
        return [
            /*
             | Le point de RETOUR de Google. Il est public parce que c'est
             | Google qui l'appelle, jamais un visiteur. Appelé à la main,
             | sans code d'autorisation exploitable, il renvoie vers la
             | connexion — et c'est exactement ce qu'il doit faire.
             |
             | Ce n'est donc pas une porte fermée par erreur : c'est une
             | porte de service, qui n'est pas faite pour être poussée
             | depuis la rue.
             */
            'auth.google.callback' => 'point de retour OAuth, appelé par Google',
        ];
    }

    /**
     * ET LES PORTES PUBLIQUES RESTENT OUVERTES.
     *
     * C'est l'assertion inverse, et celle qui protège le chiffre d'affaires :
     * un `auth` posé par réflexe sur un groupe rendrait la landing ou une
     * carte inaccessible à qui n'a pas de compte, sans aucune erreur nulle
     * part. On ne s'en apercevrait que par un client qui appelle.
     */
    public function test_the_public_doors_stay_open_to_a_guest(): void
    {
        $fermees = [];

        foreach ($this->routesSimples() as [$nom, $intergiciels]) {
            if (str_contains($intergiciels, 'auth') || isset($this->exemptionsPubliques()[$nom])) {
                continue;
            }

            // `guest` fait l'inverse à dessein : connexion et inscription
            // renvoient AILLEURS un visiteur déjà identifié. Pour un invité,
            // elles s'affichent — ce que la boucle vérifie bien.
            $reponse = $this->get(route($nom));

            // 302 est légitime pour une redirection de confort (une ancienne
            // adresse, un alias). Ce qu'on refuse, c'est un renvoi vers la
            // CONNEXION : il signifie que la porte s'est fermée.
            if ($reponse->getStatusCode() === 302
                && str_contains((string) $reponse->headers->get('Location'), 'login')) {
                $fermees[] = $nom;
            }
        }

        $this->assertSame([], $fermees,
            'Ces routes sont publiques et renvoient pourtant un visiteur vers la '.
            "connexion :\n  - ".implode("\n  - ", $fermees));
    }

    /**
     * UNE EXEMPTION NE SURVIT PAS À SA ROUTE.
     *
     * Une route supprimée laisserait son exemption derrière elle, et la
     * liste finirait par décrire une application qui n'existe plus.
     */
    public function test_no_public_exemption_outlives_its_route(): void
    {
        $existantes = array_column($this->routesSimples(), 0);
        $fantomes = array_diff(array_keys($this->exemptionsPubliques()), $existantes);

        $this->assertSame([], array_values($fantomes),
            'Ces exemptions ne correspondent à aucune route : supprimez-les.');
    }

    /**
     * LA MATRICE COUVRE QUELQUE CHOSE.
     *
     * Sans ce test, une erreur dans le filtrage ci-dessus — une condition
     * trop stricte, un nom de middleware qui change — viderait les boucles
     * et rendrait les trois tests verts en ne vérifiant rien.
     */
    public function test_the_matrix_actually_covers_routes(): void
    {
        $routes = $this->routesSimples();

        $privees = array_filter($routes, fn ($r) => str_contains($r[1], 'auth'));
        $publiques = array_filter($routes, fn ($r) => ! str_contains($r[1], 'auth'));

        $this->assertGreaterThan(10, count($privees), 'Trop peu de routes privées : le filtre est cassé.');
        $this->assertGreaterThan(5, count($publiques), 'Trop peu de routes publiques : le filtre est cassé.');
    }
}
