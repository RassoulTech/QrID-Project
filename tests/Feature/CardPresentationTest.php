<?php

namespace Tests\Feature;

use App\Enums\VarianteCarte;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LA CARTE — la référence figée, et ce qui la garde figée.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE FICHIER A ÉTÉ RÉÉCRIT, ET IL FAUT DIRE POURQUOI
 * ═══════════════════════════════════════════════════════════════════════
 * Il vérifiait _pvc.scss, la feuille des trois anciens composants de carte.
 * Ces composants ont été supprimés : la carte est désormais une référence
 * validée, transposée mécaniquement dans _card.scss et x-card.
 *
 * Ses assertions décrivaient des compositions successives — bandes,
 * colonnes verticales, ombres à trois couches — qui ont toutes été
 * remplacées. Les garder aurait été garder des tests qui verrouillent des
 * essais abandonnés.
 *
 * Ce qui suit ne vérifie plus une esthétique. Cela vérifie le CONTRAT :
 * les deux propriétés sans lesquelles tout casse, les angles vifs, la
 * stricte identité des deux variantes, et la destination de chaque QR.
 */
class CardPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function feuille(): string
    {
        return (string) file_get_contents(resource_path('sass/_card.scss'));
    }

    private function profil(string $prenom = 'Mouhamed', string $nom = 'Dione'): Profile
    {
        return Profile::factory()->create([
            'first_name' => $prenom,
            'last_name' => $nom,
            'job_title' => 'Étudiant',
            'company' => 'DigiGeek',
            'primary_color' => VarianteCarte::Blanche->value,
        ]);
    }

    private function rendu(Profile $profile, string $face, ?string $variante = null): string
    {
        return (string) view('components.card', [
            'face' => $face,
            'variant' => $variante,
            'profile' => $profile,
            'attributes' => new \Illuminate\View\ComponentAttributeBag,
        ])->render();
    }

    // =======================================================================
    // LE CONTRAT DE LA RÉFÉRENCE
    // =======================================================================

    /**
     * LES DEUX PROPRIÉTÉS QUI NE SE TOUCHENT PAS.
     *
     * Toutes les tailles de la carte sont en cqw — un pourcentage de la
     * largeur du CONTENEUR. Sans container-type, cqw se mesure sur la
     * FENÊTRE : la carte fait alors 22px de texte sur un téléphone et 115px
     * sur un grand écran. Sans aspect-ratio, une hauteur fixe la déforme dès
     * que la largeur change.
     *
     * Ce ne sont pas des préférences de mise en page. Ce sont les deux
     * conditions de fonctionnement de tout le reste.
     */
    public function test_the_card_keeps_its_container_and_its_ratio(): void
    {
        $css = $this->feuille();

        $this->assertMatchesRegularExpression(
            '/\.card\{[^}]*container-type:\s*inline-size/s',
            $css,
            'container-type a disparu : toutes les tailles en cqw se mesureront sur la fenêtre.'
        );

        $this->assertMatchesRegularExpression(
            '/\.card\{[^}]*aspect-ratio:\s*1\.586/s',
            $css,
            'Le rapport ID-1 a disparu : la carte se déformera.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\.card\{[^}]*[^-]height:\s*\d/s',
            $css,
            'Une hauteur fixe est apparue sur la carte : elle prendra le pas sur le rapport.'
        );
    }

    /**
     * LES ANGLES SUIVENT LA NORME CR80 — 3,18 mm sur 85,6 mm de large.
     *
     * Le test exigeait `border-radius:0`. C'était une erreur de fait : une
     * carte PVC au format CR80 sort de la découpe avec des coins arrondis.
     * Des angles vifs ne sont pas « la contrainte physique », ils sont ce que
     * l'imprimeur ne livre pas.
     *
     * Il protège toujours contre l'inverse — un arrondi d'interface qui
     * reviendrait par `_socle.scss`, où `.card` désigne un panneau et non
     * une carte de visite.
     */
    public function test_the_corners_follow_the_cr80_radius(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.card\{[^}]*border-radius:\s*3\.71%\s*\/\s*5\.88%/s',
            $this->feuille(),
            'Le rayon de la carte ne suit plus la norme CR80.'
        );
    }

    /**
     * LA COLONNE GARDE SON PROPRE DÉFILEMENT.
     *
     * `.adm-side` est `position: sticky; top: 0` : c'est ce `top` qui la fait
     * tenir en place pendant que le contenu défile. La feuille du bas pose
     * `inset: auto 0 0 0` sur téléphone, et il faut l'annuler au-dessus de
     * 768px.
     *
     * Écrire `inset: auto` pour cela remet les QUATRE côtés à `auto`, dont le
     * `top` — sticky perd son seuil et se comporte comme `static`. La colonne
     * défile alors avec la page, dans les deux espaces. C'est arrivé, et
     * c'est parti en production.
     *
     * Le raccourci qui écrit quatre valeurs pour en annuler trois écrase
     * toujours la quatrième qu'on n'avait pas en tête.
     */
    public function test_the_sidebar_keeps_its_own_scroll_on_desktop(): void
    {
        $css = file_get_contents(resource_path('sass/_panneau-mobile.scss'));

        $this->assertDoesNotMatchRegularExpression(
            '/^\s*inset\s*:\s*auto\s*;/m',
            $css,
            "`inset: auto` remet aussi le `top` à auto et casse le sticky de la colonne : "
            ."annuler les côtés un par un."
        );

        $this->assertMatchesRegularExpression(
            '/@include ecran\(lg\).*?\.adm-side\.offcanvas-start\s*\{[^}]*top\s*:\s*0/s',
            $css,
            'La remise à zéro pour écran large ne rend plus son `top: 0` à la colonne.'
        );
    }

    /**
     * LA COULEUR DE LA CARTE NE DÉPEND PAS DU THÈME — RÈGLE MÉTIER.
     *
     * Le client CHOISIT sa variante — blanche ou verte — à la dernière étape
     * de la création de son profil. C'est une décision de produit, imprimée
     * sur du PVC. Le thème d'affichage du visiteur n'a rien à y voir : une
     * carte blanche reste blanche en thème sombre, comme l'objet qu'on
     * recevra par la poste.
     *
     * `_theme-dark.scss` visait `.card` pour habiller les surfaces
     * d'interface, et attrapait la carte de visite au passage : elle
     * recevait un fond sombre pendant que `.card.light .who .name` gardait
     * son vert foncé, avec une spécificité supérieure. Le nom du porteur
     * devenait illisible.
     */
    public function test_the_dark_theme_never_repaints_the_business_card(): void
    {
        $this->assertStringContainsString(
            '.card:not(.light):not(.dark)',
            file_get_contents(resource_path('sass/_theme-dark.scss')),
            'Le thème sombre a recommencé à repeindre la carte de visite : '
            .'sa couleur est choisie par le client, elle ne suit pas le thème.'
        );
    }

    /**
     * LE SOCLE NE REVENDIQUE PAS LA CARTE DE VISITE.
     *
     * `.card` désigne deux objets : une surface d'interface dans le socle, la
     * carte PVC dans `_card.scss`. Le socle étant importé en dernier, il
     * gagnait et imposait son rayon de 22px à la carte, sur toutes les pages
     * qui la montrent. L'exclusion doit rester.
     */
    public function test_the_socle_excludes_the_business_card(): void
    {
        $this->assertStringContainsString(
            '.card:not(.light):not(.dark)',
            file_get_contents(resource_path('sass/_socle.scss')),
            "Le socle a recommencé à habiller la carte de visite : son rayon d'interface va l'arrondir."
        );
    }

    // =======================================================================
    // LES DEUX VARIANTES
    // =======================================================================

    /**
     * SEULE LA PALETTE CHANGE.
     *
     * La composition, les positions et les proportions sont strictement
     * identiques. On le vérifie en comparant les deux rendus une fois la
     * classe de variante retirée : ils doivent être caractère pour caractère
     * les mêmes.
     *
     * C'est la seule façon de garantir qu'une correction faite sur une
     * variante n'a pas été oubliée sur l'autre.
     */
    public function test_both_variants_share_the_exact_same_composition(): void
    {
        $profile = $this->profil();

        foreach (['recto', 'verso'] as $face) {
            $claire = $this->rendu($profile, $face, 'light');
            $sombre = $this->rendu($profile, $face, 'dark');

            $this->assertSame(
                str_replace('card light', 'card', $claire),
                str_replace('card dark', 'card', $sombre),
                "La face « {$face} » ne rend pas la même composition dans les deux variantes."
            );
        }
    }

    /** La variante suit le choix du client quand l'appelant n'en impose pas. */
    public function test_the_variant_follows_the_profile(): void
    {
        $profile = $this->profil();
        $profile->forceFill(['primary_color' => VarianteCarte::Verte->value])->save();

        $this->assertStringContainsString('card dark', $this->rendu($profile->fresh(), 'recto'));

        $profile->forceFill(['primary_color' => VarianteCarte::Blanche->value])->save();

        $this->assertStringContainsString('card light', $this->rendu($profile->fresh(), 'recto'));
    }

    // =======================================================================
    // LES DEUX QR
    // =======================================================================

    /**
     * DEUX CODES, DEUX DESTINATIONS — et ce n'est pas une erreur.
     *
     * Le recto mène au PROFIL du porteur : c'est sa carte. Le verso mène à
     * la PLATEFORME, pour que celui qui reçoit la carte puisse créer la
     * sienne. Chaque carte distribuée devient ainsi un canal d'acquisition,
     * sans rien coûter à celui qui la tend.
     *
     * Les intervertir passerait inaperçu à la relecture et se découvrirait
     * sur des cartes déjà imprimées.
     */
    public function test_each_face_carries_its_own_destination(): void
    {
        $profile = $this->profil();
        $qr = app(\App\Services\QrCodeService::class);

        /*
         | ON COMPARE LES ADRESSES ENCODÉES, PAS LES SVG.
         |
         | Deux appels successifs au générateur produisent des identifiants
         | internes différents : comparer les fichiers ferait échouer un test
         | qui a raison. Ce qui compte est la DESTINATION, et c'est elle qu'on
         | vérifie — les deux QR sont mis en cache sous une empreinte de cette
         | adresse, ils ne peuvent pas en encoder une autre.
         */
        $this->assertNotSame(
            $qr->urlEncodee($profile),
            $qr->urlPlateforme(),
            'Les deux QR mèneraient au même endroit : la carte perd son rôle de canal.'
        );

        $this->assertStringContainsString(
            $profile->slug,
            $qr->urlEncodee($profile),
            'Le QR du recto ne mène plus au profil du porteur.'
        );

        // Les deux faces portent bien un code, et un seul chacune.
        $this->assertSame(1, substr_count($this->rendu($profile, 'recto'), '<span class="qr">'));
        $this->assertSame(1, substr_count($this->rendu($profile, 'verso'), '<span class="qr">'));

        // Et le recto n'embarque pas celui de la plateforme.
        $this->assertStringNotContainsString(
            $qr->urlPlateforme(),
            $this->rendu($profile, 'recto'),
            'Le recto porte le QR de la plateforme : les deux faces ont été interverties.'
        );
    }

    private function extraireQr(string $html): string
    {
        preg_match('/<span class="qr">\s*(.*?)\s*<\/span>/s', $html, $trouve);

        return trim($trouve[1] ?? '');
    }

    // =======================================================================
    // LE NOM
    // =======================================================================

    /**
     * LE NOM TIENT SUR UNE LIGNE, ET SA TAILLE SUIT SON NOMBRE DE MOTS.
     *
     * Trois paliers, parce qu'un nom d'un seul mot et un nom de quatre
     * n'occupent pas la même place dans les 55 % de largeur de leur colonne.
     * Le comptage se fait sur les MOTS : c'est le nombre d'espaces qui décide
     * de la place réellement prise par des capitales.
     */
    public function test_the_name_class_follows_its_word_count(): void
    {
        $cas = [
            ['Awa', '', 'name name--short'],
            ['Mouhamed', 'Dione', 'name'],
            ['Abdoulaye Mouhamadou', 'Ndiaye', 'name name--long'],
            ['Abdoulaye Mouhamadou Cheikh', 'Ndiaye', 'name name--long'],
        ];

        foreach ($cas as [$prenom, $nom, $attendu]) {
            $rendu = $this->rendu($this->profil($prenom, $nom), 'recto');

            $this->assertStringContainsString(
                'class="'.$attendu.'"',
                $rendu,
                "« {$prenom} {$nom} » ne reçoit pas la classe attendue."
            );
        }

        $this->assertMatchesRegularExpression(
            '/\.who \.name\{[^}]*white-space:nowrap/s',
            $this->feuille(),
            'Le nom peut de nouveau passer à la ligne.'
        );
    }

    // =======================================================================
    // PLUS AUCUNE ANCIENNE CARTE
    // =======================================================================

    /**
     * IL N'EN RESTE AUCUNE TRACE.
     *
     * Trois composants et une feuille décrivaient la carte avant celle-ci.
     * Deux représentations d'un même objet finissent toujours par diverger,
     * et la divergence se découvre sur des cartes déjà tirées.
     */
    public function test_no_previous_card_representation_survives(): void
    {
        foreach ([
            'views/components/pvc-card.blade.php',
            'views/components/pvc-card-face-recto.blade.php',
            'views/components/pvc-card-face-verso.blade.php',
            'sass/_pvc.scss',
        ] as $mort) {
            $this->assertFileDoesNotExist(
                resource_path($mort),
                "Une ancienne représentation de la carte subsiste : {$mort}"
            );
        }
    }

    /**
     * SANS JAVASCRIPT, AUCUNE FACE N'EST MASQUÉE.
     *
     * Le balisage rend les deux faces et le bouton porte [hidden]. C'est le
     * module qui ajoute .is-flippable, et c'est cette classe seule qui
     * autorise le CSS à superposer les faces. Si le script ne se charge pas
     * — 3G coupée, module en erreur — le client voit tout.
     */
    public function test_without_javascript_both_faces_are_visible(): void
    {
        $rendu = (string) view('components.card-duo', [
            'profile' => $this->profil(),
            'variant' => null,
            'attributes' => new \Illuminate\View\ComponentAttributeBag,
        ])->render();

        $this->assertStringContainsString('class="front"', $rendu);
        $this->assertStringContainsString('class="back"', $rendu);
        $this->assertStringContainsString('data-card-duo-commande hidden', $rendu);

        // La superposition n'existe que sous .is-flippable, jamais par défaut.
        $this->assertMatchesRegularExpression(
            '/\.card-duo\.is-flippable\{/s',
            $this->feuille(),
            'La mise en scène 3D ne dépend plus de la présence du script.'
        );
    }
}
