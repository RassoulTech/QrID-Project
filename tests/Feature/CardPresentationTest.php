<?php

namespace Tests\Feature;

use App\Enums\VarianteCarte;
use App\Models\Profile;
use App\Models\User;
use App\Support\Marque;
use App\Support\NomSurCarte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * LA CARTE EST UN OBJET, ET SON LOGO EST CELUI DU PRODUIT.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CES TESTS LISENT DES FICHIERS DE STYLE
 * ═══════════════════════════════════════════════════════════════════════
 * On ne peut pas mesurer une ombre portée dans un test d'intégration. Mais on
 * peut constater qu'une RÈGLE a disparu — et c'est ce qui compte : ces
 * décisions ont chacune une raison écrite, et le défaut à craindre n'est pas
 * qu'elles rendent mal, c'est qu'elles soient retirées par mégarde lors d'un
 * remaniement, sans que personne ne s'en aperçoive avant l'impression.
 *
 * C'est la démarche déjà employée pour les coins à angle vif et l'absence de
 * troncature, deux règles qui protègent des défauts définitifs.
 */
class CardPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function pvc(): string
    {
        return (string) file_get_contents(resource_path('sass/_pvc.scss'));
    }

    private function marque(): string
    {
        return (string) file_get_contents(resource_path('sass/_brand.scss'));
    }

    // =======================================================================
    // LE LOGO
    // =======================================================================

    /**
     * LE MONOGRAMME EST CALCULÉ, JAMAIS ÉCRIT EN DUR.
     *
     * « QI » n'est pas une constante : c'est ce que donne « QrID ». Le jour où
     * le nom du produit change, le logo doit suivre partout — navbar, cartes,
     * PDF — sans qu'aucun fichier ne soit à retoucher.
     */
    public function test_the_monogram_is_derived_from_the_product_name(): void
    {
        $this->assertSame('QI', Marque::monogramme('QrID'));
        $this->assertSame('SK', Marque::monogramme('Sama Kart'));
        $this->assertSame('AB', Marque::monogramme('AlphaBeta'));

        // Un nom sans capitale ne doit pas rendre une chaîne vide.
        $this->assertNotSame('', Marque::monogramme('boutique'));
    }

    /**
     * UN SEUL COMPOSANT DE LOGO DANS TOUT LE PRODUIT.
     *
     * Le pictogramme QR générique qui servait sur la carte a été supprimé :
     * une marque qui se dessine différemment selon le support n'est plus une
     * marque.
     */
    public function test_there_is_no_second_logo_component(): void
    {
        $this->assertFileDoesNotExist(
            resource_path('views/components/brand-mark.blade.php'),
            'Un second dessin de logo est réapparu : il divergera au premier ajustement.'
        );

        $vues = array_merge(
            glob(resource_path('views/**/*.blade.php')) ?: [],
            glob(resource_path('views/*/*/*.blade.php')) ?: []
        );

        foreach ($vues as $fichier) {
            $this->assertStringNotContainsString(
                'x-brand-mark',
                (string) file_get_contents($fichier),
                'Le pictogramme générique est encore employé dans '.basename($fichier)
            );
        }
    }

    /**
     * LES LETTRES DU MONOGRAMME RESTENT BLANCHES DANS LES DEUX TONS.
     *
     * Seul le carré change de teinte. Un monogramme tantôt blanc sur vert,
     * tantôt vert sur blanc, donnerait deux logos au lieu d'un.
     */
    public function test_the_monogram_letters_stay_white_in_both_tones(): void
    {
        $css = $this->marque();

        $this->assertMatchesRegularExpression(
            '/\.brand--dark\{.*?\.brand__mark\{background:\$vert-fonce;color:\$blanc\}/s',
            $css
        );

        $this->assertMatchesRegularExpression(
            '/\.brand--light\{.*?\.brand__mark\{background:\$vert-accent;color:\$blanc\}/s',
            $css
        );
    }

    /**
     * L'APPARENCE DE LA CARTE NE DÉPEND PAS DU THÈME DE L'APPLICATION.
     *
     * Le thème sombre repeint .brand__mark en vert vif avec des lettres
     * sombres — juste pour la navbar, faux pour la carte. Une carte PVC est un
     * objet physique : son logo ne change pas de couleur selon la préférence
     * d'affichage de celui qui la regarde, sinon le client qui compose en
     * thème sombre reçoit autre chose que ce qu'il a vu.
     */
    public function test_the_card_logo_ignores_the_application_theme(): void
    {
        $css = $this->pvc();

        $this->assertStringContainsString('color:$blanc !important', $css);
        $this->assertStringContainsString('&.brand--light .brand__mark{background:$vert-accent !important}', $css);
        $this->assertStringContainsString('&.brand--dark .brand__mark{background:$vert-fonce !important}', $css);
    }

    /** Le verso porte bien le logo de l'application, et le nom une seule fois. */
    public function test_the_back_carries_the_product_logo_once(): void
    {
        $rendu = view('components.pvc-card-face-verso', [
            'variante' => VarianteCarte::Verte,
        ])->render();

        // Le carré du monogramme est présent…
        $this->assertStringContainsString('brand__mark', $rendu);
        $this->assertStringContainsString(Marque::monogramme(), $rendu);

        // …mais le composant ne rend pas son bloc de mots : le nom est écrit
        // en grand juste en dessous, et deux occurrences côte à côte
        // s'affaibliraient l'une l'autre.
        $this->assertStringNotContainsString('brand__words', $rendu);
    }

    // =======================================================================
    // LE NOM TIENT SUR UNE LIGNE
    // =======================================================================

    /**
     * LA TAILLE SUIT LA LONGUEUR DU NOM.
     *
     * C'est tout l'objet du calcul : à taille fixe, « MOUHAMED DIONE » passait
     * à la ligne — deux lignes serrées au-dessus du QR, et toute la
     * composition déséquilibrée.
     */
    public function test_a_longer_name_gets_a_smaller_size(): void
    {
        $court = NomSurCarte::taille('AWA NDIAYE', 93, 100);
        $moyen = NomSurCarte::taille('MOUHAMED DIONE', 93, 100);
        $long = NomSurCarte::taille('ABDOULAYE MOUHAMADOU NDIAYE', 93, 100);

        $this->assertGreaterThan($moyen, $court);
        $this->assertGreaterThan($long, $moyen);
    }

    /**
     * LA LARGEUR EST MESURÉE LETTRE PAR LETTRE, PAS EN MOYENNE.
     *
     * C'est le correctif qui a fait tenir « MOUHAMED DIONE » sur une ligne.
     * Une moyenne unique sous-estimait ce nom de 6 % — il porte deux M, parmi
     * les plus larges lettres qui soient — et le faisait déborder.
     *
     * Deux noms de MÊME longueur mais de lettres différentes doivent donc
     * recevoir des tailles différentes. Si ce test tombe, c'est qu'on est
     * revenu à une moyenne.
     */
    public function test_two_names_of_equal_length_are_measured_differently(): void
    {
        // Dix caractères chacun, mais l'un n'est fait que de lettres larges.
        $large = NomSurCarte::largeurEnEm('MMMMMMMMMM');
        $etroit = NomSurCarte::largeurEnEm('IIIIIIIIII');

        $this->assertGreaterThan(
            $etroit * 2,
            $large,
            'Les largeurs de lettres ne sont plus distinguées : le calcul est redevenu une moyenne.'
        );
    }

    /**
     * LE NOM REMPLIT VRAIMENT LA LARGEUR.
     *
     * C'est la demande née de la carte de référence : le nom doit courir
     * presque bord à bord. Un calcul trop prudent le laisserait flotter au
     * milieu — techniquement correct, visuellement raté.
     *
     * On exige donc au moins 92 % de la largeur utile pour tout nom de
     * longueur ordinaire.
     */
    public function test_an_ordinary_name_reaches_the_edges(): void
    {
        foreach (['AWA NDIAYE', 'MOUHAMED DIONE', 'KHADIM RASSOUL DIENE'] as $nom) {
            $taille = NomSurCarte::taille($nom, 93, 100);
            $occupe = NomSurCarte::largeurEnEm($nom) * $taille;

            $this->assertGreaterThanOrEqual(
                93 * 0.92,
                $occupe,
                "« {$nom} » n'atteint pas les bords : il flotte au milieu de la carte."
            );
        }
    }

    /**
     * TOUT NOM RENDU SUR UNE LIGNE TIENT DANS LA LARGEUR UTILE.
     *
     * La propriété qui compte n'est pas la valeur d'une taille, mais le fait
     * qu'un nom affiché sur une seule ligne ne déborde jamais — car il y
     * serait alors coupé, et un nom coupé sur une carte imprimée est un défaut
     * définitif.
     *
     * Les noms très longs sortent légitimement de cette garantie : le plancher
     * de lisibilité s'applique, surUneLigne() rend false, et le nom s'enroule
     * sur deux lignes. C'est le compromis voulu — deux lignes valent mieux
     * qu'un texte illisible, et infiniment mieux qu'un texte tronqué.
     */
    public function test_no_name_rendered_on_one_line_ever_overflows(): void
    {
        $noms = [
            'PAPA',                            // très court : le plafond s'applique
            'AWA NDIAYE',
            'CHEIKH GUEYE',
            'MOUHAMED DIONE',
            'MARIE-THÉRÈSE DIATTA',
            'SERIGNE MOUHAMADOU BAMBA FALL',   // très long : doit s'enrouler
        ];

        $surUneLigne = 0;

        foreach ($noms as $nom) {
            $taille = NomSurCarte::taille($nom, 93, 100);

            if (! NomSurCarte::surUneLigne($taille, 100)) {
                continue;   // il s'enroule : la garantie ne le concerne pas
            }

            $surUneLigne++;

            // La largeur réellement occupée, lettre par lettre — c'est la
            // mesure que le calcul cherche à borner.
            $largeurOccupee = NomSurCarte::largeurEnEm($nom) * $taille;

            $this->assertLessThanOrEqual(
                93,
                $largeurOccupee,
                "« {$nom} » est rendu sur une ligne et déborde de la carte."
            );
        }

        // Sans cette vérification, un calcul qui ferait TOUT s'enrouler
        // passerait le test en n'exécutant aucune assertion.
        $this->assertGreaterThanOrEqual(
            5,
            $surUneLigne,
            'Presque plus aucun nom ne tient sur une ligne : le calcul est devenu trop prudent.'
        );
    }

    /** Un nom très court ne devient pas plus haut que le QR Code. */
    public function test_a_very_short_name_is_capped(): void
    {
        $this->assertSame(15.0, NomSurCarte::taille('AWA', 93, 100));
    }

    /**
     * UN NOM DÉMESURÉ S'ENROULE PLUTÔT QUE DE DEVENIR ILLISIBLE.
     *
     * Le plancher protège la lisibilité à l'impression. Passé ce seuil, deux
     * lignes valent mieux qu'un texte minuscule — et infiniment mieux qu'un
     * texte tronqué, qui serait un défaut définitif sur un support imprimé.
     */
    public function test_an_extreme_name_wraps_rather_than_shrinking_forever(): void
    {
        $demesure = str_repeat('A', 60);

        $taille = NomSurCarte::taille($demesure, 93, 100);

        $this->assertSame(5.5, $taille);
        $this->assertFalse(NomSurCarte::surUneLigne($taille, 100));
    }

    /** Le nom courant est bien rendu sur une seule ligne. */
    public function test_a_normal_name_is_rendered_on_a_single_line(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create([
            'first_name' => 'Mouhamed',
            'last_name' => 'Dione',
            'slug' => 'mouhamed-dione',
        ]);

        $rendu = view('components.pvc-card-face-recto', ['profile' => $profile])->render();

        $this->assertStringContainsString('pvc__nom--ligne', $rendu);
        $this->assertStringContainsString('font-size:', $rendu);
    }

    /**
     * L'ÉCRAN ET LE PAPIER PARTAGENT LE MÊME CALCUL.
     *
     * Deux implémentations du même coefficient divergeraient, et la divergence
     * se constaterait sur des cartes déjà tirées — où le nom serait plus petit
     * qu'à l'aperçu.
     */
    public function test_screen_and_print_share_one_single_calculation(): void
    {
        $recto = (string) file_get_contents(
            resource_path('views/components/pvc-card-face-recto.blade.php')
        );
        $service = (string) file_get_contents(app_path('Services/PrintableCardService.php'));

        $this->assertStringContainsString('NomSurCarte::taille', $recto);
        $this->assertStringContainsString('NomSurCarte::taille', $service);

        // La table de largeurs n'existe qu'à un seul endroit : ni la vue ni le
        // service ne redéfinissent d'avance typographique.
        foreach ([$recto, $service] as $fichier) {
            $this->assertStringNotContainsString('AVANCE', $fichier);
            $this->assertStringNotContainsString('mb_str_split', $fichier);
        }
    }

    // =======================================================================
    // DENSITÉ
    // =======================================================================

    /**
     * MARGE RÉGULIÈRE SUR LES QUATRE CÔTÉS.
     *
     * Elle valait 6 % en haut et sur les côtés, 5 % en bas : trois valeurs
     * différentes, donc une bordure irrégulière que l'œil perçoit sans savoir
     * la nommer.
     */
    public function test_the_card_margin_is_the_same_on_all_four_sides(): void
    {
        preg_match('/\.pvc__face\{.*?padding:([^;]+);/s', $this->pvc(), $trouve);

        $this->assertNotEmpty($trouve, 'La marge de la carte est introuvable.');

        $this->assertSame(
            1,
            count(preg_split('/\s+/', trim($trouve[1]))),
            'La carte a de nouveau des marges différentes selon les côtés.'
        );
    }

    /**
     * Les trois blocs du recto se répartissent sur TOUTE la hauteur.
     *
     * Le `flex:1` du bloc QR absorbait tout l'espace disponible et centrait son
     * contenu : il en résultait deux vides inégaux, un au-dessus du nom et un
     * autre sous la fonction.
     */
    public function test_the_front_spreads_its_three_blocks(): void
    {
        $css = $this->pvc();

        $this->assertMatchesRegularExpression(
            '/\.pvc__face--recto\{\s*justify-content:space-between/s',
            $css
        );

        $this->assertStringNotContainsString('flex:1 1 auto', $css);
    }

    /** Le carré et le nom de la marque sont sur la même ligne. */
    public function test_the_brand_square_and_name_sit_on_one_line(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.pvc__v-marque\{\s*display:flex;\s*align-items:center/s',
            $this->pvc()
        );

        $rendu = view('components.pvc-card-face-verso', [
            'variante' => VarianteCarte::Verte,
        ])->render();

        // Le carré et le nom appartiennent au même conteneur.
        $this->assertMatchesRegularExpression(
            '/pvc__v-marque.*?brand__mark.*?pvc__v-nom/s',
            $rendu
        );
    }

    /**
     * Le QR occupe ≈47 % de la HAUTEUR de la carte.
     *
     * Sous 40 %, la carte paraît vide et le code devient difficile à viser.
     * Au-delà de 50 %, il écrase le nom. La largeur exprimée en cqw vaut la
     * proportion de hauteur divisée par 1,586.
     */
    public function test_the_front_qr_fills_its_share_of_the_height(): void
    {
        preg_match('/\.pvc__qr\{.*?width:([\d.]+)cqw/s', $this->pvc(), $trouve);

        $this->assertNotEmpty($trouve, 'La largeur du QR du recto est introuvable.');

        $partHauteur = ((float) $trouve[1]) * 1.586;

        $this->assertGreaterThanOrEqual(40, $partHauteur);
        $this->assertLessThanOrEqual(52, $partHauteur);
    }

    /**
     * LE QR DU VERSO EST ALIGNÉ SUR LE HAUT DU BLOC DE TEXTE.
     *
     * Deux blocs qui partent de la même ligne se lisent comme une composition ;
     * l'un centré et l'autre en haut se lisent comme deux éléments posés au
     * hasard. C'est ce que corrigeait la demande « QR remonté, aligné sur le
     * haut du bloc de texte ».
     */
    public function test_the_back_qr_is_aligned_with_the_text_block(): void
    {
        $css = $this->pvc();

        preg_match('/\.pvc__v-texte\{.*?top:([\d.]+)cqw/s', $css, $texte);
        preg_match('/\.pvc__v-qr\{.*?top:([\d.]+)cqw/s', $css, $qr);

        $this->assertNotEmpty($texte);
        $this->assertNotEmpty($qr);

        $this->assertSame(
            $texte[1],
            $qr[1],
            'Le QR du verso n\'est plus aligné sur le haut du bloc de texte.'
        );

        // Et il n'est surtout plus centré verticalement.
        $this->assertStringNotContainsString('.pvc__v-qr{'."\n".'  position:absolute;'."\n".'  right:6cqw; top:50%', $css);
    }

    // =======================================================================
    // PRÉSENTATION
    // =======================================================================

    /**
     * DEUX OMBRES, PAS UNE.
     *
     * Une courte et dense marque le CONTACT avec la surface ; une longue et
     * diffuse marque la lumière ambiante. Une ombre unique, quelle que soit sa
     * qualité, donne toujours l'impression d'un autocollant : l'œil cherche le
     * point de contact et ne le trouve pas.
     */
    public function test_the_card_casts_a_contact_shadow_and_an_ambient_one(): void
    {
        preg_match('/\.pvc__face\{.*?box-shadow:(.*?);/s', $this->pvc(), $trouve);

        $this->assertNotEmpty($trouve, 'L\'ombre de la carte est introuvable.');

        // Trois couches : contact, ambiante, arête. Deux suffiraient à
        // « avoir une ombre » — c'est la troisième qui fait l'objet.
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($trouve[1], 'rgba') + substr_count($trouve[1], 'color-mix'),
            'La carte a perdu une de ses trois couches d\'ombre.'
        );
    }

    /** Redressement au survol, ombre accentuée, et le clavier n'est pas oublié. */
    public function test_hovering_straightens_the_card_and_focus_does_the_same(): void
    {
        $css = $this->pvc();

        $this->assertStringContainsString('&:hover, &:focus-within{', $css);
        $this->assertStringContainsString('translateY(-4px)', $css);
    }

    /**
     * LE MOUVEMENT RÉDUIT EST RESPECTÉ, ET PAS « UN PEU ».
     *
     * Ce réglage système est demandé par des personnes que le mouvement rend
     * malades. La permutation doit rester fonctionnelle — c'est l'animation
     * qui disparaît, jamais la fonction.
     */
    public function test_reduced_motion_removes_every_transform(): void
    {
        $css = $this->pvc();

        // Le fichier en compte PLUSIEURS : un pour le reflet, un pour la
        // permutation. On les examine tous — n'en lire qu'un laisserait passer
        // un mouvement oublié dans l'autre.
        preg_match_all('/@media \(prefers-reduced-motion:reduce\)\{(.*?)\n\}/s', $css, $blocs);

        $this->assertNotEmpty($blocs[1], 'Le bloc de mouvement réduit a disparu.');

        $reduit = implode("\n", $blocs[1]);

        // Le survol aussi doit être neutralisé : le laisser actif rendrait le
        // réglage à moitié respecté, donc inutile.
        $this->assertStringContainsString('&:hover, &:focus-within{', $reduit);
        $this->assertStringContainsString('transition:none', $reduit);
    }

    /**
     * La carte est cliquable, MAIS elle délègue au bouton.
     *
     * Le bouton reste le chemin accessible : c'est lui qui porte aria-pressed
     * et l'étiquette qui change. Deux sources de vérité finiraient par se
     * désynchroniser.
     */
    public function test_clicking_the_card_delegates_to_the_button(): void
    {
        $js = (string) file_get_contents(resource_path('js/modules/pvc-flip.js'));

        $this->assertStringContainsString('bouton.click()', $js);

        // Un clic destiné à autre chose n'est jamais détourné : ni un lien,
        // ni une sélection de texte qu'on cherchait à copier.
        $this->assertStringContainsString("closest('a, button')", $js);
        $this->assertStringContainsString('getSelection', $js);
    }

    // =======================================================================
    // CE QUI NE DOIT PAS BOUGER
    // =======================================================================

    /** Les coins restent à angle vif, sur les deux faces et à toute taille. */
    public function test_the_corners_are_still_square(): void
    {
        $this->assertStringContainsString('border-radius:0 !important', $this->pvc());
    }

    /** Les deux variantes rendent toujours leurs deux faces. */
    public function test_both_variants_still_render_both_faces(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create(['slug' => 'awa-ndiaye']);

        foreach (VarianteCarte::cases() as $variante) {
            $profile->forceFill(['primary_color' => $variante->value])->save();

            $rendu = view('components.pvc-card', ['profile' => $profile->refresh()])->render();

            $this->assertStringContainsString('pvc__face--recto', $rendu);
            $this->assertStringContainsString('pvc__face--verso', $rendu);
            $this->assertStringContainsString('--pvc-fond:'.$variante->fond(), $rendu);
            $this->assertStringContainsString('--pvc-encre:'.$variante->encre(), $rendu);
        }
    }
}
