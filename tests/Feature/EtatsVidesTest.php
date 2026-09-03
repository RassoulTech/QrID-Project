<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UN ÉCRAN SANS DONNÉES DIT QUOI FAIRE — il ne se contente pas d'être vide.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI C'EST UN DÉFAUT ET NON UNE FINITION
 * ═══════════════════════════════════════════════════════════════════════════
 * Un tableau sans lignes, un graphique plat, une liste blanche : le lecteur
 * ne sait pas s'il n'y a rien à voir ou si quelque chose est cassé. Les deux
 * se ressemblent exactement, et c'est toujours la seconde qu'on suppose.
 *
 * Un client qui ouvre ses statistiques le lendemain de sa publication voit
 * zéro partout. S'il ne lit que des cases vides, il conclut que la mesure ne
 * marche pas — et il écrit au support. S'il lit « partagez votre carte pour
 * voir arriver vos premières vues », il sait que le compteur fonctionne et
 * ce qu'il lui reste à faire.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE TEST VÉRIFIE, ET CE QU'IL NE PEUT PAS VÉRIFIER
 * ═══════════════════════════════════════════════════════════════════════════
 * Il vérifie qu'un texte d'accompagnement EXISTE sur chaque écran rendu sans
 * la moindre donnée. Il ne juge pas sa qualité — aucun test ne sait dire si
 * une phrase est utile.
 *
 * C'est délibérément peu, et c'est déjà beaucoup : l'oubli complet est la
 * panne fréquente, pas la phrase mal tournée.
 */
class EtatsVidesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les écrans qui affichent une LISTE, et ce qu'ils montrent sans elle.
     *
     * Les écrans qui n'affichent pas de liste — un formulaire, un QR Code —
     * n'ont pas d'état vide : ils ont toujours quelque chose à montrer.
     *
     * @return list<array{string, string}> [route, rôle attendu]
     */
    private function ecransDeListe(): array
    {
        return [
            ['dashboard', 'client'],
            ['statistiques', 'client'],
            ['notifications.index', 'client'],
            // `admin.home` n'est pas listé : c'est un AIGUILLAGE. Il redirige
            // vers l'aperçu ou vers l'état système selon ce qui existe, et
            // n'affiche donc jamais rien lui-même.
            ['admin.clients.index', 'admin'],
            ['admin.profiles.index', 'admin'],
            ['admin.payments.index', 'admin'],
            ['admin.subscriptions.index', 'admin'],
            ['admin.cards.index', 'admin'],
            ['admin.audit.index', 'admin'],
            ['admin.overview', 'admin'],
            ['admin.statistics', 'admin'],
        ];
    }

    /**
     * LE MARQUEUR D'UN ÉTAT VIDE DANS CE PRODUIT.
     *
     * Trois formes coexistent — le composant `x-empty-state`, la classe
     * `db-vide` du tableau de bord, et `liste-vide` des tableaux. Les trois
     * rendent la même intention ; les chercher toutes évite d'imposer une
     * réécriture cosmétique pour satisfaire un test.
     */
    private function porteUnEtatVide(string $html): bool
    {
        foreach (['db-vide', 'empty-state', 'liste-vide', 'vide__'] as $marqueur) {
            if (str_contains($html, $marqueur)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CHAQUE ÉCRAN DE LISTE ACCOMPAGNE SON VIDE.
     *
     * La base est intacte : aucun client, aucun paiement, aucun événement.
     * C'est exactement l'état d'un produit le jour de son lancement, et
     * celui de l'administration pendant ses premières semaines.
     */
    public function test_every_list_screen_explains_its_emptiness(): void
    {
        $muets = [];

        foreach ($this->ecransDeListe() as [$route, $role]) {
            $compte = User::factory()->create([
                'email_verified_at' => now(),
                'role' => $role === 'admin' ? User::ROLE_ADMIN : User::ROLE_USER,
            ]);

            /*
             | LE CLIENT A UNE CARTE, MAIS AUCUNE DONNÉE.
             |
             | Sans carte, plusieurs écrans redirigent vers l'assistant de
             | création — ce qui est correct, mais laisse un corps vide que
             | ce test lirait comme un état vide manquant. On teste donc
             | l'état qui nous intéresse : une carte publiée que personne
             | n'a encore consultée.
             */
            if ($role === 'client') {
                Profile::factory()->create(['user_id' => $compte->id, 'is_active' => true]);
            }

            $html = $this->actingAs($compte)->get(route($route))->getContent();

            if (! $this->porteUnEtatVide($html)) {
                $muets[] = $route;
            }
        }

        $this->assertSame([], $muets,
            'Ces écrans se sont affichés sans la moindre donnée et sans rien '.
            'dire au lecteur. Une page vide et une page cassée se ressemblent '.
            "exactement :\n  - ".implode("\n  - ", $muets));
    }

    /**
     * ET LA LISTE COUVRE QUELQUE CHOSE.
     *
     * Sans ce garde-fou, une erreur dans la table ci-dessus — une route
     * renommée, un tableau vidé — rendrait le test vert en ne vérifiant rien.
     */
    public function test_the_list_of_screens_is_not_empty(): void
    {
        $this->assertGreaterThan(8, count($this->ecransDeListe()));
    }
}
