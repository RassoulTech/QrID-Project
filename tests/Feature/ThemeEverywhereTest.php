<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LE THÈME S'APPLIQUE PARTOUT, ET SE BASCULE DE PARTOUT.
 *
 * Deux exigences distinctes, souvent confondues :
 *
 * 1. la CLASSE sur <html> doit être posée sur tous les gabarits — sinon un
 *    écran reste blanc au milieu d'un parcours sombre ;
 * 2. le BOUTON doit être atteignable avant d'avoir un compte. Une bascule
 *    réservée aux clients connectés impose le thème clair précisément aux
 *    écrans que l'on découvre en premier.
 *
 * La deuxième est celle qui se casse silencieusement : il suffit que la route
 * retombe dans le groupe « auth » lors d'un remaniement pour que la landing
 * et la page de connexion perdent la bascule, sans qu'aucune page ne plante.
 */
class ThemeEverywhereTest extends TestCase
{
    use RefreshDatabase;

    /** Les écrans publics, ceux qu'on voit sans compte. */
    private function ecransPublics(): array
    {
        return [
            'landing' => route('home'),
            'connexion' => route('login'),
            'création de compte' => route('register'),
            'mot de passe oublié' => route('password.request'),
            'conditions générales' => route('legal.conditions'),
        ];
    }

    // =======================================================================
    // LA BASCULE EST ATTEIGNABLE SANS COMPTE
    // =======================================================================

    public function test_a_visitor_can_switch_theme_without_an_account(): void
    {
        $this->post(route('preferences.theme'), ['theme' => Theme::SOMBRE])
            ->assertRedirect()
            ->assertCookie(Theme::nomDuCookie(), Theme::SOMBRE, encrypted: false);
    }

    /**
     * Le cookie posé doit réellement teindre la page suivante. Écrire une
     * préférence que personne ne relit ne bascule rien.
     */
    public function test_the_guest_cookie_actually_darkens_the_next_page(): void
    {
        // Sans préférence d'abord. Le cookie de test reste attaché au client
        // une fois posé : les deux passes doivent rester séparées, sinon la
        // seconde contamine la première.
        foreach ($this->ecransPublics() as $nom => $url) {
            $this->assertStringNotContainsString(
                'theme-dark',
                $this->get($url)->getContent(),
                "L'écran « {$nom} » démarre déjà en sombre alors qu'aucune préférence n'existe."
            );
        }

        $this->withUnencryptedCookie(Theme::nomDuCookie(), Theme::SOMBRE);

        foreach ($this->ecransPublics() as $nom => $url) {
            $this->assertStringContainsString(
                'theme-dark',
                $this->get($url)->getContent(),
                "L'écran « {$nom} » ignore la préférence sombre du visiteur."
            );
        }
    }

    /** Le bouton lui-même, pas seulement la route derrière. */
    public function test_the_toggle_button_is_present_on_public_screens(): void
    {
        foreach (['landing' => route('home'), 'connexion' => route('login')] as $nom => $url) {
            $this->get($url)
                ->assertSee(route('preferences.theme'), escape: false)
                ->assertSee('Passer en thème sombre');

            $this->assertStringContainsString(
                'class="theme-toggle"',
                $this->get($url)->getContent(),
                "Aucun bouton de bascule sur l'écran « {$nom} »."
            );
        }
    }

    // =======================================================================
    // LA CLASSE EST POSÉE SUR TOUS LES GABARITS
    // =======================================================================

    /**
     * Un écran par gabarit : app, auth, public, public-profile, admin.
     * Si l'un d'eux oublie html-open.blade.php, il apparaît ici.
     */
    public function test_every_layout_carries_the_theme_class(): void
    {
        $client = User::factory()->create(['theme' => Theme::SOMBRE, 'email_verified_at' => now()]);
        $admin = User::factory()->create([
            'theme' => Theme::SOMBRE,
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $ecrans = [
            'public' => [null, route('home')],
            'auth' => [null, route('login')],
            'public-profile' => [null, route('profile.demo')],
            'app' => [$client, route('dashboard')],
            'admin' => [$admin, route('admin.system.health')],
        ];

        foreach ($ecrans as $gabarit => [$utilisateur, $url]) {
            $requete = $utilisateur !== null
                ? $this->actingAs($utilisateur)
                : $this->withUnencryptedCookie(Theme::nomDuCookie(), Theme::SOMBRE);

            $this->assertStringContainsString(
                'theme-dark',
                $requete->get($url)->getContent(),
                "Le gabarit « {$gabarit} » ne pose pas la classe de thème sur <html>."
            );
        }
    }

    // =======================================================================
    // LES DEUX MÉMOIRES
    // =======================================================================

    /** Le compte prime : la préférence suit la personne, pas le navigateur. */
    public function test_the_account_wins_over_the_cookie(): void
    {
        $utilisateur = User::factory()->create([
            'theme' => Theme::CLAIR,
            'email_verified_at' => now(),
        ]);

        $this->assertStringNotContainsString(
            'theme-dark',
            $this->actingAs($utilisateur)
                ->withUnencryptedCookie(Theme::nomDuCookie(), Theme::SOMBRE)
                ->get(route('dashboard'))->getContent(),
            'Un cookie laissé par un autre visiteur écrase la préférence du compte.'
        );
    }

    /** Pour un compte, la bascule écrit en base ET dans le cookie. */
    public function test_switching_while_logged_in_writes_both_memories(): void
    {
        $utilisateur = User::factory()->create([
            'theme' => Theme::CLAIR,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($utilisateur)
            ->post(route('preferences.theme'), ['theme' => Theme::SOMBRE])
            ->assertCookie(Theme::nomDuCookie(), Theme::SOMBRE, encrypted: false);

        $this->assertSame(Theme::SOMBRE, $utilisateur->fresh()->theme);
    }

    public function test_an_unknown_theme_is_refused_for_guests_too(): void
    {
        $this->from(route('home'))
            ->post(route('preferences.theme'), ['theme' => 'fluo'])
            ->assertSessionHasErrors('theme');
    }
}
