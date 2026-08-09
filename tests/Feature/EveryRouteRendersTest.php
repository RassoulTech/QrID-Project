<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * GARDE-FOU — chaque page GET de l'application doit s'afficher.
 *
 * Une vue manquante, un appel à une route inexistante, une variable non
 * transmise : autant d'erreurs qui ne se voient qu'au clic. Ce test les
 * attrape toutes, sur TOUTES les routes, sans en oublier une.
 *
 * Il parcourt la table de routage réelle : ajouter une route l'ajoute au test,
 * il n'y a rien à maintenir ici.
 */
class EveryRouteRendersTest extends TestCase
{
    use RefreshDatabase;

    /** Routes qu'on ne peut pas appeler à l'aveugle, avec la raison. */
    private const IGNOREES = [
        'registration.confirm',   // exige un jeton signé à usage unique
        'registration.abandon',   // idem
        'password.reset',         // exige un jeton de réinitialisation
        'profile.demo',           // dépend d'un profil publié en base
    ];

    /**
     * Aucun fournisseur de données ici, et c'est volontaire : PHPUnit les
     * évalue AVANT que l'application ne soit démarrée. Toute lecture de la
     * table de routage y échoue sur « A facade root has not been set », le
     * fournisseur est signalé invalide, et le test ne s'exécute jamais — c'est
     * exactement ce qui se passait. On parcourt donc les routes depuis le test.
     */
    public function test_no_get_route_returns_a_server_error(): void
    {
        [$user, $remplacements] = $this->contexte();

        $echecs = [];

        foreach (self::routesGet() as $nom => [, $uri]) {
            // Substitue les paramètres d'URL par des valeurs réelles.
            foreach ($remplacements as $cle => $valeur) {
                $uri = str_replace(['{'.$cle.'}', '{'.$cle.'?}'], $valeur, $uri);
            }

            // Une route dont il reste un paramètre inconnu n'est pas testable ainsi.
            if (str_contains($uri, '{')) {
                continue;
            }

            $code = $this->actingAs($user)->get('/'.ltrim($uri, '/'))->getStatusCode();

            // 200, une redirection ou un 404 métier sont des réponses valides.
            // Une 500 signifie une vue manquante ou une variable oubliée.
            if ($code >= 500) {
                $echecs[] = "{$nom} ({$uri}) → {$code}";
            }
        }

        $this->assertSame([], $echecs, "Routes en erreur serveur :\n".implode("\n", $echecs));
    }

    /**
     * Le même parcours, mais pour un utilisateur QUI POSSÈDE UN PROFIL.
     *
     * C'est exactement le cas qui a produit l'erreur : le dashboard actif
     * n'était jamais rendu par les tests, seul l'état vide l'était.
     */
    public function test_every_page_renders_for_a_user_who_owns_a_profile(): void
    {
        [$user] = $this->contexte(avecProfil: true);

        $echecs = [];

        foreach ($this->routesGetConcretes() as $nom => $uri) {
            $code = $this->actingAs($user)->get('/'.ltrim($uri, '/'))->getStatusCode();

            if ($code >= 500) {
                $echecs[] = "{$nom} ({$uri}) → {$code}";
            }
        }

        $this->assertSame([], $echecs, "Pages en erreur serveur :\n".implode("\n", $echecs));
    }

    /** Et pour un utilisateur dont le profil est ACTIF, avec des statistiques. */
    public function test_every_page_renders_for_a_user_with_an_active_profile(): void
    {
        [$user, , $profile] = $this->contexte(avecProfil: true, actif: true);

        ProfileEvent::factory()->count(3)->create([
            'profile_id' => $profile->id,
            'type' => ProfileEvent::TYPE_VIEW,
        ]);

        $echecs = [];

        foreach ($this->routesGetConcretes() as $nom => $uri) {
            $code = $this->actingAs($user)->get('/'.ltrim($uri, '/'))->getStatusCode();

            if ($code >= 500) {
                $echecs[] = "{$nom} ({$uri}) → {$code}";
            }
        }

        $this->assertSame([], $echecs, "Pages en erreur serveur :\n".implode("\n", $echecs));

        // Le profil public doit s'ouvrir : c'est le lien que le dashboard affiche.
        $this->get(route('profile.public', $profile->slug))->assertOk();
    }

    /** Les compteurs sont lus en UNE requête : la carte ne doit pas en semer. */
    /**
     * Le tableau de bord lit profile_events en un nombre CONSTANT de requêtes.
     *
     * Il en faut trois, et trois seulement : les compteurs agrégés,
     * l'histogramme groupé par jour, la liste des derniers visiteurs. Trois
     * questions différentes, trois requêtes bornées — ce n'est pas un N+1.
     *
     * Ce qui compte, c'est que ce nombre NE BOUGE PAS avec le volume
     * d'événements. On mesure donc deux fois, sur 5 puis sur 200 lignes : si
     * une boucle de requêtes s'introduisait un jour, l'écart le dirait
     * immédiatement.
     */
    public function test_the_dashboard_reads_its_events_in_a_constant_number_of_queries(): void
    {
        [$user, , $profile] = $this->contexte(avecProfil: true, actif: true);

        $compter = function () use ($user): int {
            $requetes = 0;

            DB::listen(function ($q) use (&$requetes) {
                if (str_contains($q->sql, 'profile_events')) {
                    $requetes++;
                }
            });

            $this->actingAs($user)->get(route('dashboard'))->assertOk();

            DB::flushQueryLog();

            return $requetes;
        };

        ProfileEvent::factory()->count(5)->create(['profile_id' => $profile->id]);
        $petit = $compter();

        ProfileEvent::factory()->count(200)->create(['profile_id' => $profile->id]);
        $grand = $compter();

        $this->assertSame(3, $petit, 'Attendu : compteurs, histogramme, visiteurs.');
        $this->assertSame(
            $petit,
            $grand,
            'Le nombre de requêtes grandit avec le volume : une boucle s\'est glissée quelque part.'
        );
    }

    // -----------------------------------------------------------------------

    public static function routesGet(): array
    {
        $cas = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $nom = $route->getName();

            if (! $nom || in_array($nom, self::IGNOREES, true)) {
                continue;
            }

            $cas[$nom] = [$nom, $route->uri()];
        }

        return $cas;
    }

    /** Routes GET sans paramètre : appelables directement. */
    private function routesGetConcretes(): array
    {
        $uris = [];

        foreach (self::routesGet() as $nom => [$n, $uri]) {
            if (! str_contains($uri, '{')) {
                $uris[$nom] = $uri;
            }
        }

        return $uris;
    }

    /** @return array{0:User, 1:array<string,string>, 2:?Profile} */
    private function contexte(bool $avecProfil = false, bool $actif = false): array
    {
        $this->seed(TemplateSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $profile = null;

        if ($avecProfil) {
            $profile = Profile::factory()->create([
                'user_id' => $user->id,
                'is_active' => $actif,
            ]);

            Subscription::factory()->active()->create([
                'user_id' => $user->id,
                'plan_id' => Plan::factory()->create()->id,
            ]);
        }

        return [$user, ['slug' => $profile?->slug ?? 'inconnu'], $profile];
    }
}
