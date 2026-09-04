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
 * LE PANNEAU « PLUS » NE RÉPÈTE PAS LE DOCK.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE DÉFAUT QUE CE FICHIER EMPÊCHE DE REVENIR
 * ═══════════════════════════════════════════════════════════════════════════
 * Sur téléphone, l'espace client affiche deux navigations : le dock en bas,
 * et le panneau que son bouton « Plus » ouvre.
 *
 * Le dock portait Tableau de bord, Profil, Carte et Statistiques — et
 * « Plus » rouvrait exactement les quatre mêmes, plus une cinquième. Quatre
 * entrées sur cinq faisaient double emploi. On demandait au pouce de choisir
 * entre deux chemins vers la même page, dont l'un coûtait un geste de plus.
 *
 * Un menu « Plus » ne peut contenir que ce qui ne tient PAS ailleurs. Sinon
 * ce n'est pas un menu, c'est une copie.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER VÉRIFIE, ET COMMENT
 * ═══════════════════════════════════════════════════════════════════════════
 * La divergence entre les deux surfaces est portée par la feuille de style :
 * le serveur ne connaît pas la largeur de l'écran, il rend donc UNE seule
 * liste, et le CSS masque sous `lg` ce que le dock porte déjà.
 *
 * Les deux moitiés du dispositif sont donc vérifiées :
 *
 *   · le MARQUAGE — chaque destination du dock est marquée dans le panneau,
 *     sans quoi le CSS n'aurait rien à masquer ;
 *   · la RÈGLE — le CSS COMPILÉ porte bien la règle qui masque, et celle qui
 *     rétablit au-dessus de la rupture.
 *
 * Lire le CSS compilé plutôt que la source SCSS est délibéré : c'est le
 * fichier que le navigateur reçoit. Une règle écrasée plus loin dans la
 * cascade, un `@include` mal placé, un fichier oublié dans l'ordre des
 * imports — rien de tout cela ne se voit dans la source.
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

    /** Le panneau latéral seul, découpé du reste de la page. */
    private function panneau(string $html): string
    {
        $debut = mb_strpos($html, 'id="menuLateral"');
        $this->assertNotFalse($debut, 'Le panneau latéral est absent de la page.');

        $reste = mb_substr($html, $debut);

        return mb_substr($reste, 0, mb_strpos($reste, '</aside>'));
    }

    // =======================================================================
    // LE MARQUAGE
    // =======================================================================

    /**
     * CHAQUE DESTINATION DU DOCK EST MARQUÉE DANS LE PANNEAU.
     *
     * Le compte est vérifié, pas seulement la présence : marquer trois
     * entrées sur quatre laisserait un doublon, et un seul doublon suffit à
     * ramener le défaut.
     */
    public function test_every_dock_destination_is_marked_in_the_panel(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();

        $attendues = count(Navigation::routesDuDockClient());
        $marquees = substr_count($this->panneau($html), 'adm-nav__item--dans-le-dock');

        $this->assertSame($attendues, $marquees,
            "Le panneau marque {$marquees} entrée(s) alors que le dock en porte ".
            "{$attendues}. Toute entrée non marquée restera visible sur téléphone ".
            'et fera double emploi avec le dock.');
    }

    /**
     * ET IL RESTE QUELQUE CHOSE À OUVRIR.
     *
     * L'excès inverse : un bouton « Plus » qui ouvre une feuille vide est
     * pire qu'un doublon.
     */
    public function test_the_panel_still_has_something_the_dock_does_not(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();

        $elements = array_slice(preg_split('/<li\b/', $this->panneau($html)), 1);

        $restantes = array_filter(
            $elements,
            fn ($e) => ! str_contains($e, 'adm-nav__item--dans-le-dock'),
        );

        $this->assertNotEmpty($restantes,
            'Le panneau « Plus » ne porte plus aucune destination propre : le '.
            'bouton ouvrirait une feuille vide.');
    }

    /**
     * LES DEUX LISTES VIENNENT DE LA MÊME SOURCE.
     *
     * C'est ce qui empêche la divergence de revenir. Si quelqu'un change le
     * dock dans la coque sans toucher à Navigation, le panneau masquerait
     * une destination que le dock ne propose pas — elle deviendrait
     * inatteignable sur téléphone, et rien ne le signalerait.
     */
    public function test_the_dock_renders_exactly_the_routes_navigation_declares(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();

        $debut = mb_strpos($html, 'class="dock"');
        $this->assertNotFalse($debut, 'Le dock est absent de la page.');

        $reste = mb_substr($html, $debut);
        $dock = mb_substr($reste, 0, mb_strpos($reste, '</nav>'));

        foreach (Navigation::routesDuDockClient() as $route) {
            $this->assertStringContainsString(route($route), $dock,
                "Le dock ne mène pas à « {$route} », que Navigation déclare ".
                'pourtant. Le panneau masquera cette destination sans que le '.
                'dock ne la propose : elle deviendra inatteignable sur téléphone.');
        }
    }

    // =======================================================================
    // LA RÈGLE DE STYLE
    // =======================================================================

    /** Ce qui est marqué est bien masqué. */
    public function test_the_compiled_css_hides_what_the_dock_already_carries(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.adm-nav__item--dans-le-dock\s*\{[^}]*display\s*:\s*none/',
            $this->cssCompile(),
            'Le CSS compilé ne masque pas les entrées déjà portées par le dock : '.
            'le panneau « Plus » les répétera sur téléphone.');
    }

    /**
     * ET RÉTABLI SUR ÉCRAN LARGE, OÙ IL N'Y A PLUS DE DOCK.
     *
     * C'est la moitié qu'on oublie. Sans elle, quatre destinations
     * disparaîtraient de la colonne de gauche sur ordinateur — et personne
     * ne le verrait en testant sur téléphone.
     */
    public function test_the_compiled_css_restores_them_on_wide_screens(): void
    {
        /*
         | LES DEUX ÉCRITURES DE LA RUPTURE SONT ACCEPTÉES.
         |
         | Le SCSS écrit `min-width`, mais le compilateur rend aujourd'hui la
         | syntaxe d'intervalle : `@media (width>=768px)`. Un test qui
         | n'accepterait que `min-width` tomberait au prochain changement
         | d'outil sans qu'aucune règle n'ait bougé — et l'on prendrait
         | l'habitude de le corriger sans le lire.
         */
        $this->assertMatchesRegularExpression(
            '/@media[^{]*(min-width|width\s*>=)[^{]*\{[^@]*\.adm-nav__item--dans-le-dock\s*\{[^}]*display\s*:\s*list-item/',
            $this->cssCompile(),
            'Le CSS ne rétablit pas ces entrées au-dessus de la rupture du dock. '.
            "Sur ordinateur, où aucun dock n'existe, la colonne perdrait quatre ".
            'destinations.');
    }
}
