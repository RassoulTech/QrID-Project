<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LES BOUTONS SOUS LA CARTE — ceux qu'un iPhone recouvrait.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LA PANNE, ET POURQUOI ELLE ÉTAIT INTROUVABLE
 * ═══════════════════════════════════════════════════════════════════════════
 * « Voir le verso » chevauchait le bas de la carte sur un iPhone réel, son
 * libellé à moitié coupé. Sur simulateur de bureau, à 320 comme à 375px, la
 * mesure était irréprochable : 16px entre le bas de la carte et le bouton,
 * 44px de haut, aucun chevauchement, aucun débordement.
 *
 * LA CAUSE N'ÉTAIT PAS UNE MARGE, C'ÉTAIT L'ORDRE DE PEINTURE.
 *
 * La scène porte `perspective`, ses faces `preserve-3d` et
 * `backface-visibility`. Safari promeut cet ensemble en couche GPU. Le
 * bouton, lui, n'était ni positionné ni transformé : sans couche propre, le
 * compositeur restait libre de peindre la carte par-dessus lui. Les moteurs
 * de bureau tranchent en faveur de l'ordre du document ; Safari mobile, non.
 *
 * C'est la panne la plus coûteuse à diagnostiquer : elle est invisible
 * partout où on la cherche, et un bouton recouvert ne paraît pas cassé — il
 * ne répond simplement pas au doigt.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS PEUVENT ET NE PEUVENT PAS FAIRE
 * ═══════════════════════════════════════════════════════════════════════════
 * Aucun test PHP ne reproduit le compositeur de Safari. Ils vérifient donc
 * que la PARADE est en place — le contexte de positionnement qui rend
 * l'ordre de peinture explicite au lieu de le laisser au compositeur.
 *
 * C'est exactement ce qu'un test peut garantir ici : que personne ne
 * retirera par mégarde deux lignes dont l'effet ne se voit sur aucune
 * machine de développement.
 */
class BoutonsDeLaCarteTest extends TestCase
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

    /**
     * Une règle donne-t-elle à ce sélecteur un contexte de positionnement
     * complet — `position` ET `z-index` ? L'un sans l'autre laisse l'ordre
     * de peinture dépendre de l'ordre du document, ce qui est précisément
     * ce que Safari n'honore pas ici.
     */
    private function assertCoucheExplicite(string $selecteur, string $pourquoi): void
    {
        $css = $this->cssCompile();
        $echappe = preg_quote($selecteur, '/');

        preg_match_all('/'.$echappe.'\s*\{([^}]*)\}/', $css, $trouvees);

        $regles = implode(';', $trouvees[1] ?? []);

        $this->assertMatchesRegularExpression('/position\s*:\s*relative/', $regles,
            "{$selecteur} n'établit pas de contexte de positionnement. {$pourquoi}");

        $this->assertMatchesRegularExpression('/z-index\s*:\s*[1-9]/', $regles,
            "{$selecteur} n'a pas de plan explicite. {$pourquoi}");
    }

    // =======================================================================
    // LA PARADE
    // =======================================================================

    public function test_the_flip_control_has_its_own_paint_layer(): void
    {
        $this->assertCoucheExplicite('.card-duo__commande',
            'Sur iPhone, la carte — couche 3D promue par Safari — sera peinte '.
            'par-dessus « Voir le verso », dont le libellé apparaîtra coupé par '.
            'le bas de la carte. Rien ne le montrera sur un simulateur de bureau.');
    }

    public function test_the_action_buttons_have_their_own_paint_layer(): void
    {
        $this->assertCoucheExplicite('.db-carte__cote',
            'Sur iPhone, la carte peut recouvrir les boutons de téléchargement '.
            'du QR, « Carte imprimable », « Modifier ma carte » et « Partager '.
            'sur WhatsApp ». Un bouton recouvert ne reçoit pas le toucher : il '.
            'ne paraît pas cassé, il ne répond simplement pas.');
    }

    // =======================================================================
    // LES CIBLES RESTENT ATTEIGNABLES
    // =======================================================================

    /**
     * LE BOUTON DE PERMUTATION GARDE SES 44px.
     *
     * C'est la hauteur minimale d'une cible tactile (loi 5). Elle est écrite
     * en dur dans la règle du bouton, et rien ne la rappelle ailleurs.
     */
    public function test_the_flip_button_keeps_a_reachable_touch_target(): void
    {
        $css = $this->cssCompile();

        preg_match('/\.card-duo__bouton\s*\{([^}]*)\}/', $css, $trouvee);

        $this->assertNotEmpty($trouvee[1] ?? '',
            'La règle du bouton de permutation a disparu du CSS compilé.');

        preg_match('/min-height\s*:\s*(\d+)px/', $trouvee[1], $hauteur);

        $this->assertGreaterThanOrEqual(44, (int) ($hauteur[1] ?? 0),
            'Le bouton « Voir le verso » descend sous 44px : au doigt, on le '.
            'manque une fois sur trois.');
    }

    // =======================================================================
    // LE LIBELLÉ QUI CHANGE
    // =======================================================================

    /**
     * LES DEUX ÉTIQUETTES SONT DANS LE HTML, DONC TRADUITES.
     *
     * Elles étaient écrites en dur, en français, dans le module JavaScript.
     * Un client anglophone voyait donc « View the back » — rendu par Blade,
     * correctement traduit — repasser en français dès la première pression.
     */
    public function test_both_labels_are_rendered_by_the_server(): void
    {
        $html = $this->actingAs($this->client())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-libelle-verso="'.__('card.voir_verso').'"', $html);
        $this->assertStringContainsString('data-libelle-recto="'.__('card.voir_recto').'"', $html);
    }

    /** Et elles existent dans les deux langues. */
    public function test_the_labels_exist_in_english_too(): void
    {
        app()->setLocale('en');

        foreach (['card.voir_verso', 'card.voir_recto'] as $cle) {
            $this->assertNotSame($cle, __($cle),
                "La clé « {$cle} » n'est pas traduite en anglais : le bouton ".
                'afficherait sa propre clé.');
        }

        $this->assertNotSame(__('card.voir_verso'), __('card.voir_recto'),
            'Les deux faces portent le même libellé anglais : le bouton ne dirait '.
            'plus quelle face il montre.');
    }

    /**
     * ET LE MODULE NE LES RÉÉCRIT PLUS EN DUR.
     *
     * Le garde-fou porte sur le fichier source : c'est là que la tentation
     * revient, une chaîne littérale étant plus courte à écrire qu'un
     * attribut de données.
     */
    public function test_the_script_no_longer_hardcodes_french(): void
    {
        $module = file_get_contents(resource_path('js/modules/card-duo.js'));

        // Les commentaires expliquent le défaut : on ne lit que le code.
        $code = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $module);

        foreach (['Voir le recto', 'Voir le verso'] as $litteral) {
            $this->assertStringNotContainsString("'".$litteral."'", $code,
                "Le module réécrit « {$litteral} » en dur. Le bouton repasserait ".
                'en français pour un client anglophone dès la première pression.');
        }

        $this->assertStringContainsString('libelleRecto', $code,
            'Le module ne lit plus les étiquettes depuis le HTML : elles ne '.
            'peuvent plus être traduites.');
    }
}
