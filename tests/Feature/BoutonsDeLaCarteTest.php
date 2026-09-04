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
    // TROIS DÉPENDANCES RETIRÉES — chacune pouvait, seule, produire le
    // recouvrement observé sur iPhone
    // =======================================================================

    /**
     * 1. LA HAUTEUR DE LA SCÈNE EST MESURÉE, PLUS CALCULÉE.
     *
     * Les DEUX faces étaient absolues : la scène n'avait aucun contenu dans
     * le flux, et toute sa hauteur venait de `aspect-ratio`. Deux nombres
     * devaient alors tomber d'accord — la hauteur réservée et celle que la
     * carte peint. Ils tombaient d'accord sur Chromium, pas sur l'iPhone.
     *
     * Le recto reste désormais dans le flux : c'est lui qui donne sa hauteur
     * à la scène, et cette hauteur est celle que le navigateur a réellement
     * mesurée. Les deux nombres ne peuvent plus diverger — il n'y en a qu'un.
     */
    public function test_the_scene_height_comes_from_a_card_in_the_flow(): void
    {
        $css = $this->cssCompile();

        preg_match('/\.card-duo\.is-flippable\s+\.card-duo__face--recto\s*\{([^}]*)\}/', $css, $recto);

        $this->assertNotEmpty($recto[1] ?? '',
            'La règle du recto a disparu du CSS compilé.');

        $this->assertStringNotContainsString('position:absolute', str_replace(' ', '', $recto[1]),
            'Le recto est de nouveau retiré du flux. La scène retombe alors sur '.
            "`aspect-ratio` seul pour sa hauteur, et cette hauteur calculée peut ".
            'de nouveau diverger de celle que la carte peint réellement — c\'est '.
            'ce qui faisait chevaucher « Voir le verso » sur iPhone.');

        preg_match('/\.card-duo\.is-flippable\s+\.card-duo__scene\s*\{([^}]*)\}/', $css, $scene);

        $this->assertStringNotContainsString('aspect-ratio', $scene[1] ?? '',
            'La scène impose de nouveau son propre rapport de forme, en plus de '.
            'celui de la carte qu\'elle contient. Deux nombres qui doivent tomber '.
            "d'accord finissent toujours par ne plus le faire.");
    }

    /**
     * 2. L'ÉCART SOUS LA CARTE NE DÉPEND PLUS DE `gap`.
     *
     * `gap` n'existe en flex que depuis Safari 14.5, et là où il n'est pas
     * connu il ne dégrade pas : il ne fait rien. L'écart devenait nul et le
     * bouton venait toucher le bas de la carte, ombre comprise.
     *
     * Une marge est comprise partout, depuis toujours.
     */
    public function test_the_gap_below_the_card_survives_without_flex_gap(): void
    {
        $css = $this->cssCompile();

        preg_match('/\.card-duo__commande\s*\{([^}]*)\}/', $css, $commande);

        $this->assertMatchesRegularExpression('/margin-top\s*:\s*[1-9]/', $commande[1] ?? '',
            "L'écart entre la carte et son bouton ne repose plus que sur le `gap` ".
            'du conteneur flex. Là où `gap` n\'est pas connu — Safari avant 14.5 — '.
            'il ne vaut rien, et le bouton vient toucher la carte.');
    }

    /**
     * 3. LES FACES NE PORTENT PLUS `transform-style: preserve-3d`.
     *
     * Cette propriété sert à faire vivre les ENFANTS d'un élément dans son
     * espace 3D. Une face de carte n'a pas d'enfant en 3D — ce qui pivote,
     * c'est elle.
     *
     * Elle n'était pas seulement inutile. `.card` porte `overflow:hidden`, et
     * la spécification dit qu'un débordement masqué ramène `transform-style`
     * à `flat`. Un moteur qui ne tranche pas cette contradiction cesse de
     * découper le contenu de la carte au bord de la carte — et ce contenu
     * déborde alors sur ce qui suit, c'est-à-dire sur le bouton.
     */
    public function test_the_faces_do_not_contradict_the_cards_clipping(): void
    {
        $css = $this->cssCompile();

        preg_match('/\.card-duo\.is-flippable\s+\.card-duo__face\s*\{([^}]*)\}/', $css, $face);

        $this->assertNotEmpty($face[1] ?? '',
            'La règle commune aux deux faces a disparu du CSS compilé.');

        $this->assertStringNotContainsString('preserve-3d', $face[1],
            'Les faces portent de nouveau `preserve-3d`, qui contredit le '.
            '`overflow:hidden` de la carte. Un moteur qui suit `preserve-3d` '.
            'plutôt que la spécification cesse de découper la carte à son bord, '.
            'et son contenu déborde sur le bouton placé dessous.');

        // La permutation, elle, doit rester : c'est `backface-visibility` seul
        // qui cache la face tournée de dos.
        $this->assertStringContainsString('backface-visibility:hidden', str_replace(' ', '', $face[1]),
            'Sans `backface-visibility`, les deux faces se voient en même temps : '.
            'la carte affiche son recto et son verso superposés.');
    }

    /**
     * 4. LES DEUX FACES NE PEUVENT PAS AVOIR DES HAUTEURS DIFFÉRENTES.
     *
     * Le défaut se voyait à l'œil, deux captures à la main : au recto le
     * bouton tombait juste, au verso la carte descendait plus bas et le
     * bouton chevauchait son bord. Ce n'était donc pas le bouton qui bougeait
     * — c'était la carte qui grandissait en se retournant.
     *
     * Le verso porte plus de contenu que le recto : un logo, une accroche, un
     * QR Code, un appel à l'action, un pied. Tant que sa hauteur pouvait
     * dépendre de ce contenu, elle pouvait dépasser celle du recto, qui fixe
     * la scène.
     *
     * `inset:0` le lui interdit : sa hauteur vient des décalages, donc de la
     * scène, donc du recto. Les deux faces sont la même boîte.
     */
    public function test_the_back_face_cannot_be_taller_than_the_front(): void
    {
        $css = $this->cssCompile();

        preg_match('/\.card-duo\.is-flippable\s+\.card-duo__face--verso\s*\{([^}]*)\}/', $css, $verso);

        $regle = str_replace(' ', '', $verso[1] ?? '');

        $this->assertNotEmpty($regle, 'La règle du verso a disparu du CSS compilé.');

        $this->assertStringContainsString('position:absolute', $regle,
            'Le verso est revenu dans le flux. Il porte plus de contenu que le '.
            'recto — logo, accroche, QR, pied — et la carte grandira donc en se '.
            'retournant, jusqu\'à passer sous le bouton.');

        $this->assertMatchesRegularExpression('/inset:0|top:0/', $regle,
            'Le verso ne se cale plus sur la scène : sa hauteur peut redevenir '.
            'celle de son contenu, et différer de celle du recto.');
    }

    // =======================================================================
    // LES LIBELLÉS NE SORTENT PAS DE LEUR PILULE
    // =======================================================================

    /**
     * « PARTAGER SUR WHATSAPP » DÉBORDAIT, ET DEUX AUTRES PASSAIENT DE PEU.
     *
     * Mesuré au rendu, à 393px de fenêtre — un iPhone 15 — quand la grille
     * offrait deux colonnes de 156px, soit 124px de texte :
     *
     *     « QR en PNG »              66px   tient
     *     « Carte imprimable »      103px   tient, 21px de marge
     *     « Modifier ma carte »     106px   tient, 18px de marge
     *     « Partager sur WhatsApp » 137px   DÉBORDE de 13px
     *
     * Le libellé est centré : il sortait donc des DEUX côtés de la pilule, ce
     * qui le faisait aussi paraître décentré.
     *
     * ═══════════════════════════════════════════════════════════════════════
     * ÉLARGIR LA COLONNE NE SUFFIT PAS, ET CE TEST GARDE L'AUTRE MOITIÉ
     * ═══════════════════════════════════════════════════════════════════════
     * La colonne est passée à 170px, ce qui règle le cas mesuré. Mais une
     * largeur calibrée sur les libellés d'aujourd'hui, en français, ne
     * protège ni d'une traduction plus longue, ni d'une police au rendu plus
     * large, ni d'un libellé qu'on rallongera.
     *
     * Le REPLI, lui, protège de tous les cas : le libellé passe à la ligne et
     * la pilule grandit. Vérifié au rendu — un libellé de 69 caractères tient
     * sur deux lignes dans une pilule passée de 44 à 49px, sans un pixel de
     * débordement.
     *
     * C'est cette moitié-là que le test garde, parce que c'est celle qu'on
     * retire par mégarde en croyant que la largeur suffit.
     */
    public function test_a_long_label_wraps_instead_of_leaving_its_pill(): void
    {
        $css = $this->cssCompile();

        // Le sélecteur nomme les éléments pour l'emporter sur le socle, qui
        // pose `white-space:nowrap` et est chargé en dernier.
        preg_match('/\.board-downloads>a,\s*\.board-downloads>button\s*\{([^}]*)\}/',
            str_replace(' >', '>', str_replace('> ', '>', $css)), $trouvee);

        $regle = str_replace(' ', '', $trouvee[1] ?? '');

        $this->assertNotEmpty($regle,
            'La règle qui autorise le repli des libellés a disparu. Un libellé '.
            'trop long ressortira de sa pilule, des deux côtés.');

        $this->assertStringContainsString('white-space:normal', $regle,
            'Les libellés ne se replient plus : `%bouton` impose `nowrap`, et un '.
            'libellé trop long sortira de la pilule au lieu de passer à la ligne.');

        $this->assertStringContainsString('height:auto', $regle,
            "La pilule a de nouveau une hauteur fixe : `.btn-pill` en impose 46. ".
            'Un libellé replié sortirait par le bas au lieu de sortir par le côté '.
            "— on aurait déplacé le débordement, pas supprimé.");
    }

    /** Et la pilule reste une cible atteignable au doigt. */
    public function test_the_action_buttons_keep_a_reachable_touch_target(): void
    {
        $css = $this->cssCompile();

        preg_match('/\.board-downloads>a,\s*\.board-downloads>button\s*\{([^}]*)\}/',
            str_replace(' >', '>', str_replace('> ', '>', $css)), $trouvee);

        preg_match('/min-height:\s*(\d+)px/', $trouvee[1] ?? '', $hauteur);

        $this->assertGreaterThanOrEqual(44, (int) ($hauteur[1] ?? 0),
            'En rendant la hauteur libre, on a perdu le plancher tactile de 44px : '.
            'au doigt, on manque le bouton une fois sur trois.');
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
