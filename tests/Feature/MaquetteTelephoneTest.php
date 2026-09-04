<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LA MAQUETTE MONTRE LA CARTE QU'ON RECEVRA.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LA PROMESSE FAUSSE QUE CE FICHIER EMPÊCHE
 * ═══════════════════════════════════════════════════════════════════════════
 * `x-phone` portait une COPIE À LA MAIN de la page publique : ses propres
 * balises, ses propres classes `.phc`, ses propres tailles « divisées par
 * deux ». Son commentaire affirmait suivre `public/profile.blade.php` « bloc
 * pour bloc ».
 *
 * Il ne le suivait plus. La page publique a supprimé le médaillon rond et
 * posé l'identité DANS une image unique en pleine largeur ; la copie montrait
 * encore un portrait rond sur un bandeau vert, le nom posé à côté. Deux
 * compositions opposées — et c'est celle qui n'existe nulle part que voyait
 * le visiteur avant de s'inscrire.
 *
 * On ne promet pas à quelqu'un un produit qui n'est pas le sien.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUI REND LA DÉRIVE IMPOSSIBLE
 * ═══════════════════════════════════════════════════════════════════════════
 * La maquette rend désormais `x-carte-publique` — le composant lui-même,
 * celui que sert /p/{slug} — réduit par une transformation d'échelle. Il n'y
 * a plus deux structures à tenir d'accord : il n'y en a qu'une.
 *
 * Reste UN endroit où une copie subsiste, et il est étroit : une requête
 * média interroge la fenêtre et non le bloc qui la contient, donc la carte
 * prendrait sa mise en page de bureau à l'intérieur du téléphone. Six règles
 * la ramènent à ses valeurs de base dans `.phone__page`.
 *
 * Ces six règles sont exactement ce qui peut dériver. Les tests ci-dessous ne
 * les relisent donc pas : ils COMPARENT chacune à la valeur de base de la
 * carte, dans le même CSS compilé. Changer la couverture de la page publique
 * sans suivre ici rend le test rouge.
 */
class MaquetteTelephoneTest extends TestCase
{
    use RefreshDatabase;

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

    /** La valeur d'une propriété dans la règle EXACTE d'un sélecteur. */
    private function valeur(string $css, string $selecteur, string $propriete): ?string
    {
        $motif = '/(?<![\w.-])'.preg_quote($selecteur, '/').'\s*\{([^}]*)\}/';

        if (! preg_match($motif, $css, $regle)) {
            return null;
        }

        if (! preg_match('/(?:^|;)\s*'.preg_quote($propriete, '/').'\s*:\s*([^;]+)/', $regle[1], $valeur)) {
            return null;
        }

        return trim($valeur[1]);
    }

    /**
     * La règle de base de la carte et celle qui la rétablit dans le téléphone
     * disent-elles la même chose ?
     */
    private function assertMaquetteSuitLaCarte(string $propriete, string $selecteur): void
    {
        $css = $this->cssCompile();

        $base = $this->valeur($css, $selecteur, $propriete);
        $maquette = $this->valeur($css, '.phone__page '.$selecteur, $propriete);

        $this->assertNotNull($base,
            "La règle de base `{$selecteur} { {$propriete} }` est introuvable dans le CSS compilé.");

        $this->assertNotNull($maquette,
            "La maquette ne rétablit plus `{$propriete}` sur `{$selecteur}`. Sur un ".
            'écran large, le téléphone de la vitrine montrera la mise en page de '.
            'BUREAU de la carte — une requête média interroge la fenêtre, pas le '.
            'bloc qui la contient.');

        $this->assertSame($base, $maquette,
            "La carte publique rend `{$propriete}: {$base}` sur `{$selecteur}`, mais la ".
            "maquette rétablit `{$maquette}`. La vitrine montre autre chose que le produit.");
    }

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

    // =======================================================================
    // LA MAQUETTE EST LA CARTE, PAS UNE RESSEMBLANCE
    // =======================================================================

    /**
     * ELLE REND LE COMPOSANT DE LA PAGE PUBLIQUE.
     *
     * C'est le test central : tout le reste en découle. Si la maquette
     * redevient une copie, elle cessera de porter ces classes.
     */
    public function test_the_mockup_renders_the_real_public_card(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['pubc__carte', 'pubc__couverture', 'pubc__identite', 'pubc__infos', 'pubc__grille'] as $classe) {
            $this->assertStringContainsString($classe, $html,
                "L'accueil ne rend pas `{$classe}` : la maquette a cessé d'être la ".
                'carte publique et redessine sa propre version.');
        }
    }

    /**
     * ET IL NE RESTE AUCUNE TRACE DE LA COPIE.
     *
     * Les classes `.phc` étaient le vocabulaire de la copie. Leur retour
     * signifierait qu'on a recommencé à redessiner la carte à côté.
     */
    public function test_the_hand_made_copy_is_gone(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('phc__', $html,
            'La copie à la main de la carte publique est de retour dans la maquette.');

        $this->assertStringNotContainsString('phc__', $this->cssCompile(),
            'La feuille de styles de la copie est de retour.');
    }

    /**
     * PAS DE MÉDAILLON — c'est la divergence exacte qui a été signalée.
     *
     * La page publique a supprimé le portrait rond au profit d'une image
     * unique en pleine largeur. La maquette le montrait encore.
     */
    public function test_the_mockup_has_no_medallion(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('medaillon', $html,
            'La maquette affiche un médaillon. La carte publique n\'en a plus : '.
            "elle porte UNE image en pleine largeur, avec l'identité posée dessus.");
    }

    /** Et elle porte bien une image, comme la vraie carte. */
    public function test_the_mockup_carries_a_single_cover_image(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('pubc__couverture-image', $html,
            'La maquette ne montre aucune image de couverture : elle affiche le '.
            "décor de repli, alors que la vitrine doit montrer ce qu'on obtient ".
            'en téléversant une photo.');

        $this->assertStringContainsString('couverture-demo.svg', $html,
            'La couverture de la maquette n\'est plus l\'illustration dessinée. '.
            'Une vitrine ne présente pas une personne réelle comme un client.');

        $this->assertFileExists(public_path('images/couverture-demo.svg'),
            "L'illustration de couverture est référencée mais absente : la ".
            "maquette afficherait une image cassée sur la page la plus visitée.");
    }

    // =======================================================================
    // LES SIX RÈGLES QUI PEUVENT DÉRIVER
    // =======================================================================

    public function test_the_mockup_keeps_the_phone_cover_height(): void
    {
        $this->assertMaquetteSuitLaCarte('height', '.pubc__couverture');
    }

    public function test_the_mockup_keeps_the_phone_action_grid(): void
    {
        $this->assertMaquetteSuitLaCarte('grid-template-columns', '.pubc__grille');
    }

    /**
     * LA CARTE OCCUPE TOUT L'APPAREIL.
     *
     * Au-delà de 640px, la page publique détache la carte : coins arrondis,
     * ombre, bordure, fond gris autour. C'est juste sur un écran de bureau,
     * et faux dans un cadre de téléphone — l'appareil montrerait une carte
     * flottant dans une marge grise au lieu d'une page.
     */
    public function test_the_card_fills_the_device(): void
    {
        $css = $this->cssCompile();

        $regle = null;
        if (preg_match('/(?<![\w.-])\.phone__page \.pubc__carte\s*\{([^}]*)\}/', $css, $trouvee)) {
            $regle = str_replace(' ', '', $trouvee[1]);
        }

        $this->assertNotNull($regle,
            'La maquette ne ramène plus la carte à sa mise en page pleine largeur.');

        foreach (['display:block', 'border-radius:0', 'box-shadow:none'] as $attendu) {
            $this->assertStringContainsString(str_replace(' ', '', $attendu), $regle,
                "La carte de la maquette ne rétablit pas `{$attendu}` : sur un écran ".
                'large elle flotterait dans une marge au lieu d\'occuper l\'appareil.');
        }
    }

    /**
     * L'APERÇU CLIENT MONTRE LA COUVERTURE DU CLIENT, PAS L'ILLUSTRATION.
     *
     * `x-phone` sert deux surfaces qui ne promettent pas la même chose :
     *
     *   la VITRINE   montre à un inconnu ce que le produit sait faire, avec
     *                un visuel dessiné — on n'y présente personne de réel ;
     *   l'APERÇU     montre à quelqu'un SA carte, juste avant qu'il paie
     *                pour l'activer.
     *
     * Glisser l'illustration de la vitrine dans l'aperçu ferait payer une
     * carte que le client n'a jamais composée. C'est le moment du parcours où
     * une promesse fausse coûte le plus cher.
     */
    public function test_the_client_preview_shows_the_clients_own_cover(): void
    {
        // L'aperçu précède l'activation : une carte déjà en ligne renvoie au
        // tableau de bord, l'aperçu n'ayant alors plus d'objet.
        $client = User::factory()->create(['email_verified_at' => now()]);
        Profile::factory()->create(['user_id' => $client->id, 'is_active' => false]);

        $html = $this->actingAs($client)
            ->get(route('profile.preview'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('couverture-demo.svg', $html,
            "L'aperçu montre l'illustration de la vitrine à la place de la ".
            'couverture du client. Il paierait pour une carte qui n\'est pas la sienne.');
    }

    // =======================================================================
    // CE QUE RENDRE DEUX FOIS LE MÊME COMPOSANT POUVAIT CASSER
    // =======================================================================

    /**
     * DEUX MAQUETTES SUR UNE PAGE NE FONT PAS DEUX FOIS LE MÊME IDENTIFIANT.
     *
     * `x-carte-publique` portait `id="carte"` en dur — l'ancre de retour de
     * la feuille de partage. L'accueil rend deux téléphones, /design-system
     * en rend trois : le document devenait invalide, et « fermer » renvoyait
     * vers une carte qui n'est pas celle qu'on regarde.
     */
    public function test_two_mockups_do_not_duplicate_an_id(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(0, substr_count($html, 'id="carte"'),
            "L'accueil rend plusieurs fois `id=\"carte\"`. Un identifiant en double ".
            'est invalide, et le premier gagne.');
    }

    /**
     * MAIS LA VRAIE PAGE PUBLIQUE LE GARDE.
     *
     * C'est elle qui porte la feuille de partage, et le lien « fermer » a
     * besoin de cette ancre pour ne pas être un `href="#"` mort.
     */
    public function test_the_real_public_page_keeps_the_anchor(): void
    {
        $client = $this->client();
        $slug = $client->profile->slug;

        $html = $this->get(route('profile.public', $slug))->assertOk()->getContent();

        $this->assertStringContainsString('id="carte"', $html,
            "La page publique a perdu l'ancre de retour : le lien « fermer » de la ".
            'feuille de partage ne mène plus nulle part.');
    }

    /**
     * LA MAQUETTE N'OFFRE AUCUN LIEN AU CLAVIER.
     *
     * C'est une IMAGE de la carte, pas la carte. Sans `inert`, l'accueil
     * offrirait une douzaine de liens invisibles à l'œil, et un visiteur qui
     * tabule se retrouverait piégé dans une maquette.
     */
    public function test_the_mockup_is_out_of_reach_of_the_keyboard(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="phone__vue"[^>]*\binert\b/', $html,
            'La maquette est atteignable au clavier : ses liens décoratifs entrent '.
            'dans le parcours de tabulation de la page la plus visitée.');
    }
}
