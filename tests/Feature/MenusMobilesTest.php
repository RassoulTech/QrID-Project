<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LA NAVIGATION DES COQUES — haut, bas, et rien de perdu entre les deux.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════════
 * Depuis la direction A, l'espace client navigue par des ONGLETS en haut sur
 * ordinateur et une BARRE D'ONGLETS ANCRÉE en bas sur téléphone. Plus de
 * colonne, plus de hamburger, plus de panneau « Plus ». Trois défauts
 * restent possibles, et ce fichier les empêche :
 *
 *   · DIVERGENCE — une destination présente en haut et absente en bas, donc
 *     inatteignable au pouce. Les deux barres doivent lire App\Support\
 *     Navigation, et tout ce qui manque à la barre du bas doit se trouver
 *     dans le menu du compte (un <details>, sans JavaScript).
 *   · RECOUVREMENT — une barre fixe qui cache la fin du contenu : le corps
 *     de page doit lui réserver sa hauteur.
 *   · REPÈRE PERDU — l'entrée active doit se distinguer AU REPOS (fond ET
 *     couleur) et porter aria-current.
 *
 * L'administration garde une colonne (onze rubriques) ; sur téléphone, sa
 * barre du bas ouvre la colonne par « Plus », et la colonne ne répète pas
 * ce que la barre porte déjà — l'ancienne garantie, conservée.
 *
 * LIRE LE CSS COMPILÉ plutôt que les sources est délibéré : c'est le
 * fichier que le navigateur reçoit, cascade et ordre d'import compris.
 */
class MenusMobilesTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $plan = Plan::factory()->create(['slug' => 'standard', 'duration_days' => 30]);
        $u = User::factory()->create(['email_verified_at' => now()]);

        Subscription::factory()->create([
            'user_id' => $u->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);

        Profile::factory()->create(['user_id' => $u->id, 'is_active' => true]);

        return $u;
    }

    private function administrateur(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /** Le CSS que le navigateur reçoit réellement. */
    private function cssCompile(): string
    {
        $manifeste = json_decode(file_get_contents(public_path('build/manifest.json')), true);

        $feuilles = [];

        foreach ($manifeste as $entree) {
            foreach ($entree['css'] ?? [] as $fichier) {
                $feuilles[] = $fichier;
            }

            if (str_ends_with($entree['file'] ?? '', '.css')) {
                $feuilles[] = $entree['file'];
            }
        }

        $this->assertNotEmpty($feuilles, 'Aucune feuille compilée : lancez `npm run build`.');

        return implode('', array_map(
            fn ($f) => file_get_contents(public_path('build/'.$f)),
            array_unique($feuilles),
        ));
    }

    /** Un fragment de la page, de son marqueur d'ouverture à sa fermeture. */
    private function fragment(string $html, string $debut, string $fin): string
    {
        $position = mb_strpos($html, $debut);
        $this->assertNotFalse($position, "Le fragment « {$debut} » est absent de la page.");

        $reste = mb_substr($html, $position);

        return mb_substr($reste, 0, mb_strpos($reste, $fin));
    }

    // =======================================================================
    // L'ESPACE CLIENT — deux barres, une seule liste
    // =======================================================================

    /** La barre du bas mène exactement aux routes que Navigation déclare. */
    public function test_the_bottom_bar_renders_exactly_the_routes_navigation_declares(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();
        $barre = $this->fragment($html, 'class="onglets-bas"', '</nav>');

        foreach (Navigation::routesDuDockClient() as $route) {
            $this->assertStringContainsString(route($route), $barre,
                "La barre du bas ne mène pas à « {$route} », que Navigation déclare.");
        }

        $this->assertSame(count(Navigation::routesDuDockClient()), substr_count($barre, 'onglets-bas__lien'),
            'La barre du bas porte une entrée que Navigation ne déclare pas.');

        $this->assertLessThanOrEqual(5, substr_count($barre, 'onglets-bas__lien'),
            'Plus de cinq entrées : à 320px, les libellés se tronquent.');
    }

    /** Les onglets du haut couvrent TOUTES les destinations. */
    public function test_the_top_tabs_carry_every_client_destination(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();
        $onglets = $this->fragment($html, 'class="coque-onglets"', '</nav>');

        foreach (Navigation::ongletsClient() as $onglet) {
            $this->assertStringContainsString(route($onglet['route']), $onglets,
                "L'onglet « {$onglet['route']} » manque en haut.");
        }
    }

    /**
     * RIEN N'EST PERDU SUR TÉLÉPHONE.
     *
     * Ce que la barre du bas ne porte pas doit être atteignable AILLEURS sur
     * la même page, sans JavaScript : dans le menu du compte, un <details>.
     */
    public function test_what_the_bottom_bar_lacks_is_in_the_account_menu(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();
        $menu = $this->fragment($html, 'class="menu-coque menu-compte"', '</details>');

        $absentes = array_diff(
            array_column(Navigation::ongletsClient(), 'route'),
            Navigation::routesDuDockClient(),
        );

        $this->assertNotEmpty($absentes, 'Six destinations pour cinq places : une doit vivre ailleurs.');

        foreach ($absentes as $route) {
            $this->assertStringContainsString(route($route), $menu,
                "« {$route} » n'est ni dans la barre du bas ni dans le menu du compte : ".
                'elle est inatteignable sur téléphone.');
        }

        $this->assertStringContainsString('data-menu', $menu,
            'Le menu du compte doit être un <details> : il s\'ouvre sans JavaScript.');
    }

    /** Plus de hamburger ni de panneau latéral pour naviguer dans l'espace client. */
    public function test_the_client_shell_has_no_hamburger_nor_side_panel(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-bs-toggle="offcanvas"', $html,
            'Un panneau latéral réapparaît dans l\'espace client.');
        $this->assertStringNotContainsString('adm-side', $html,
            'La colonne latérale réapparaît dans l\'espace client.');
    }

    /** L'onglet actif porte aria-current, en haut comme en bas. */
    public function test_the_active_entry_is_announced(): void
    {
        $html = $this->actingAs($this->client())->get(route('statistiques'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="coque-onglet is-actif"\s+aria-current="page"/', $html);
        $this->assertMatchesRegularExpression('/class="onglets-bas__lien is-actif"\s+aria-current="page"/', $html);
    }

    // =======================================================================
    // LA RÈGLE DE STYLE — ancrée, opaque, réservée, distinguée
    // =======================================================================

    /** Ancrée en bas, opaque, sans flou ; masquée à partir de 768px. */
    public function test_the_compiled_css_anchors_the_bottom_bar(): void
    {
        $css = $this->cssCompile();

        $this->assertMatchesRegularExpression(
            '/\.onglets-bas\s*\{[^}]*position\s*:\s*fixed[^}]*bottom\s*:\s*0/', $css,
            'La barre du bas n\'est plus ancrée au bord.');

        $this->assertDoesNotMatchRegularExpression(
            '/\.onglets-bas[^{]*\{[^}]*backdrop-filter/', $css,
            'Un flou d\'arrière-plan sur un élément présent au défilement : interdit.');

        $this->assertMatchesRegularExpression(
            '/@media[^{]*(min-width|width\s*>=)\s*:?\s*768px[^{]*\{[^@]*\.onglets-bas\s*\{[^}]*display\s*:\s*none/',
            $css, 'La barre du bas doit disparaître à 768px, où les onglets du haut prennent le relais.');
    }

    /** Le corps de page réserve la hauteur de la barre : rien n'est recouvert. */
    public function test_the_page_reserves_room_for_the_bottom_bar(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.avec-onglets-bas\s*\{[^}]*padding-bottom\s*:\s*calc\(76px/', $this->cssCompile(),
            'La barre du bas recouvrira la fin du contenu — et c\'est toujours le bouton d\'envoi.');

        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('<body class="avec-onglets-bas">', $html);
    }

    /** L'entrée active se distingue AU REPOS : fond ET couleur. */
    public function test_the_active_entry_is_distinguished_at_rest(): void
    {
        $css = $this->cssCompile();

        $this->assertMatchesRegularExpression(
            '/\.onglets-bas__lien\.is-actif[^{]*\{[^}]*background\s*:[^}]*color\s*:/', $css,
            'L\'entrée active de la barre du bas ne se distingue plus par le fond et la couleur.');
    }

    /** Le bouton d'aide ne flotte plus au-dessus du contenu. */
    public function test_the_help_button_no_longer_floats_over_content(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/\.aide-wa[^{]*\{[^}]*position\s*:\s*fixed/', $this->cssCompile(),
            'Le bouton d\'aide est redevenu flottant : il recouvrira du contenu.');
    }

    // =======================================================================
    // L'ADMINISTRATION — la colonne ne répète pas la barre du bas
    // =======================================================================

    /** Chaque destination de la barre du bas est marquée dans la colonne. */
    public function test_every_admin_bar_destination_is_marked_in_the_column(): void
    {
        $html = $this->actingAs($this->administrateur())->get(route('admin.overview'))->assertOk()->getContent();
        $colonne = $this->fragment($html, 'id="menuAdmin"', '</aside>');

        $this->assertSame(count(Navigation::routesDuDockAdmin()), substr_count($colonne, 'adm-nav__item--dans-le-dock'),
            'La colonne ne marque pas toutes les rubriques de la barre du bas : elle les répétera sur téléphone.');

        $restantes = array_filter(
            array_slice(preg_split('/<li\b/', $colonne), 1),
            fn ($e) => ! str_contains($e, 'adm-nav__item--dans-le-dock'),
        );

        $this->assertNotEmpty($restantes, '« Plus » ouvrirait une colonne vide.');
    }

    /** « Plus » s'ouvre aussi sans JavaScript : un lien vers l'ancre de la colonne. */
    public function test_the_admin_more_entry_works_without_javascript(): void
    {
        $html = $this->actingAs($this->administrateur())->get(route('admin.overview'))->assertOk()->getContent();

        $this->assertStringContainsString('href="#menuAdmin"', $html);
        $this->assertMatchesRegularExpression('/\.adm-side\.offcanvas-start:target/', $this->cssCompile());
    }

    /** Masqué sur téléphone, rétabli sur écran large où il n'y a plus de barre. */
    public function test_the_compiled_css_hides_then_restores_marked_entries(): void
    {
        $css = $this->cssCompile();

        $this->assertMatchesRegularExpression(
            '/\.adm-nav__item--dans-le-dock\s*\{[^}]*display\s*:\s*none/', $css);

        $this->assertMatchesRegularExpression(
            '/@media[^{]*(min-width|width\s*>=)[^{]*\{[^@]*\.adm-nav__item--dans-le-dock\s*\{[^}]*display\s*:\s*list-item/',
            $css, 'Sur ordinateur, la colonne perdrait quatre rubriques.');
    }
}
