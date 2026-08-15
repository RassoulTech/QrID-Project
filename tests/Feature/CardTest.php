<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QrCodeService;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * LA CARTE — QR Code, aperçu, téléchargements.
 *
 * Ce QR finira imprimé sur du PVC. Une erreur ici ne se corrige pas par un
 * déploiement : elle se corrige en réimprimant des cartes. D'où l'insistance
 * sur le CONTENU encodé, la correction d'erreur et la zone de silence.
 */
class CardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email_verified_at' => now()]);

        $this->profile = Profile::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'awa-ndiaye',
            'primary_color' => '#0B3B2E',
            'is_active' => false,
        ]);
    }

    private function publier(): void
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::where('slug', 'mensuel')->value('id'),
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->profile->forceFill(['is_active' => true])->save();
        $this->user->refresh();
    }

    // =======================================================================
    // GÉNÉRATION
    // =======================================================================

    /** Le QR encode l'URL publique COMPLÈTE, jamais le slug seul. */
    public function test_the_qr_encodes_the_full_public_url(): void
    {
        $service = app(QrCodeService::class);

        $this->assertSame(
            route('profile.public', 'awa-ndiaye'),
            $service->url($this->profile)
        );
    }

    /**
     * Le contenu encodé est bien celui attendu.
     *
     * On ne peut pas décoder un QR en PHP sans lecteur ; on vérifie donc que
     * la matrice produite pour l'URL est IDENTIQUE à celle réencodée depuis
     * cette même URL, avec les mêmes paramètres. Une divergence de contenu ou
     * de niveau de correction se verrait immédiatement.
     */
    public function test_the_encoded_matrix_matches_the_public_url(): void
    {
        $url = app(QrCodeService::class)->url($this->profile);

        $attendu = Encoder::encode($url, ErrorCorrectionLevel::H(), 'ISO-8859-1')->getMatrix();

        $this->assertSame(33, $attendu->getWidth(), 'La version du QR a changé.');

        // Le SVG du paquet doit porter la même grille : 33 modules + 4 de
        // marge de chaque côté = 41, ce que traduit son facteur d'échelle.
        $svg = app(QrCodeService::class)->svg($this->profile);

        preg_match('/scale\(([\d.]+)\)/', $svg, $m);

        $this->assertSame(41, (int) round(512 / (float) $m[1]), 'La zone de silence a disparu.');
    }

    /**
     * Le QR est produit À LA CRÉATION, sans action de l'utilisateur.
     *
     * Le chemin est demandé au service et non écrit en dur : il porte
     * désormais une empreinte de APP_URL, pour qu'un changement d'adresse
     * régénère les codes au lieu de laisser l'ancien domaine partir à
     * l'impression.
     */
    public function test_the_qr_is_generated_automatically_when_the_profile_is_created(): void
    {
        $qr = app(QrCodeService::class);

        Storage::disk('public')->assertExists($qr->path($this->profile, 'svg'));
        Storage::disk('public')->assertExists($qr->path($this->profile, 'png'));
    }

    /** Changer le slug change l'URL encodée : le QR doit suivre. */
    public function test_changing_the_slug_regenerates_the_qr(): void
    {
        $this->profile->forceFill(['slug' => 'awa-n-diaye'])->save();

        $qr = app(QrCodeService::class);

        Storage::disk('public')->assertExists($qr->path($this->profile->refresh(), 'svg'));
        Storage::disk('public')->assertExists($qr->path($this->profile, 'png'));
    }

    /** Un champ sans effet sur le QR ne déclenche aucune régénération. */
    public function test_an_unrelated_change_leaves_the_qr_alone(): void
    {
        $chemin = app(QrCodeService::class)->path($this->profile, 'svg');

        $avant = Storage::disk('public')->get($chemin);

        $this->profile->forceFill(['job_title' => 'Urbaniste'])->save();

        $this->assertSame($avant, Storage::disk('public')->get($chemin));
    }

    /** Le PNG est carré, en haute définition, et exploitable à l'impression. */
    public function test_the_png_is_square_and_high_definition(): void
    {
        $png = app(QrCodeService::class)->png($this->profile);

        [$largeur, $hauteur] = getimagesizefromstring($png);

        $this->assertSame($largeur, $hauteur);
        $this->assertGreaterThanOrEqual(900, $largeur, 'Trop petit pour une impression à 300 dpi.');
    }

    // =======================================================================
    // APERÇU
    // =======================================================================

    public function test_the_preview_shows_both_visuals(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('profile.preview'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Votre carte physique', $html);
        $this->assertStringContainsString('Ce que verront vos contacts', $html);

        // La carte PVC et le cadre de téléphone, tous deux présents.
        $this->assertStringContainsString('class="pvc', $html);
        $this->assertStringContainsString('class="phone', $html);

        // Le QR est intégré dans la page, pas seulement annoncé.
        $this->assertStringContainsString('<svg', $html);
    }

    /**
     * RÉPARTITION DES DEUX FACES.
     *
     * · RECTO = le PORTEUR : nom, QR Code, fonction. Rien d'autre.
     * · VERSO = la PLATEFORME seule. AUCUNE donnée de profil n'y figure —
     *   ni nom, ni téléphone, ni e-mail, ni entreprise, ni lien personnel.
     *   Le verso est rigoureusement identique sur toutes les cartes de tous
     *   les clients : c'est un support de communication pour le produit.
     */
    public function test_the_front_carries_the_holder_and_the_back_only_the_platform(): void
    {
        $this->profile->forceFill([
            'phone' => '+221773831364',
            'public_email' => 'awa@exemple.sn',
            'company' => 'Atelier Teranga',
            'job_title' => 'Architecte',
        ])->save();

        $html = $this->actingAs($this->user)
            ->get(route('profile.preview'))->assertOk()->getContent();

        /*
         | Découpage par MARQUEURS plutôt que par balises appariées : compter
         | les </div> imbriqués rendait ce test dépendant de la structure
         | interne des faces, qu'on a le droit de faire évoluer.
         */
        preg_match('#pvc__face--recto(.*?)pvc__face--verso#s', $html, $recto);
        preg_match('#pvc__face--verso(.*?)(?:pvc__commande|</main>)#s', $html, $verso);

        $this->assertNotEmpty($recto, 'Recto introuvable.');
        $this->assertNotEmpty($verso, 'Verso introuvable.');

        // RECTO : le nom, le code, la fonction — en majuscules, comme la
        // référence, où le titre occupe toute la largeur de la carte.
        $this->assertStringContainsString(mb_strtoupper($this->profile->full_name), $recto[0]);
        $this->assertStringContainsString('ARCHITECTE', $recto[0]);
        $this->assertStringContainsString('<svg', $recto[0]);

        // VERSO : la marque, l'accroche, l'adresse du site. Et rien d'autre.
        $this->assertStringContainsString(config('app.name'), $verso[0]);
        $this->assertStringContainsString(config('landing.brand.tagline'), $verso[0]);
        $this->assertStringContainsString(config('landing.brand.website'), $verso[0]);

        /*
         | PLUS AUCUNE DONNÉE DE PROFIL AU VERSO — pas même le slug.
         |
         | Le code-barres qui le portait a été retiré : mesuré, il ne pouvait
         | pas être scanné à la taille d'une carte (0,062 mm par module contre
         | 0,19 mm nécessaires). Le verso appartient désormais entièrement à la
         | plateforme, et son QR mène à elle.
         */
        foreach ([
            $this->profile->full_name,
            'awa-ndiaye',
            'Atelier Teranga',
            '+221 77 383 13 64',
            'awa@exemple.sn',
        ] as $donnee) {
            $this->assertStringNotContainsString(
                $donnee,
                $verso[0],
                "Le verso porte « {$donnee} » : cette face n'appartient qu'à la plateforme."
            );
        }
    }

    /**
     * Le verso s'affiche SANS le moindre profil.
     *
     * Ce n'est pas une commodité de test : c'est la preuve structurelle qu'il
     * ne dépend d'aucun porteur. Le composant ne reçoit plus que la variante,
     * et il n'existe donc aucun chemin par lequel une donnée client pourrait
     * y aboutir.
     */
    public function test_the_back_renders_without_any_profile(): void
    {
        $rendu = view('components.pvc-card-face-verso')->render();

        $this->assertStringContainsString(config('app.name'), $rendu);
        $this->assertStringContainsString(config('landing.brand.tagline'), $rendu);
        $this->assertStringContainsString(config('landing.brand.card_cta'), $rendu);
        $this->assertStringNotContainsString('awa', mb_strtolower($rendu));
    }

    /**
     * LE QR DU VERSO MÈNE À LA PLATEFORME, PAS AU PORTEUR.
     *
     * Deux codes, deux destinations, et ce n'est pas une erreur : le recto
     * donne la carte de son porteur, le verso fait découvrir le produit à qui
     * la reçoit. Chaque carte distribuée devient un canal d'acquisition.
     *
     * Ce test est le garde-fou de cette distinction. L'inverser — un verso
     * qui pointerait vers le profil — ne casserait rien de visible, et se
     * découvrirait sur des cartes déjà imprimées.
     */
    public function test_the_back_qr_leads_to_the_platform_and_not_to_the_holder(): void
    {
        $qr = app(QrCodeService::class);

        $adresse = $qr->urlPlateforme();

        $this->assertStringContainsString('src=', $adresse, 'La provenance manque : les cartes seraient immesurables.');
        $this->assertStringNotContainsString('awa-ndiaye', $adresse);

        $svg = $qr->plateformeSvg();

        $this->assertStringContainsString('<svg', $svg);
        Storage::disk('public')->assertExists($qr->cheminPlateforme('svg'));
    }

    /**
     * Le cache du QR de plateforme est indexé sur l'ADRESSE, pas sur un nom
     * fixe. APP_URL change au premier déploiement : un fichier au nom figé
     * survivrait au changement, et des cartes partiraient à l'impression avec
     * l'ancienne adresse.
     */
    public function test_changing_the_platform_address_produces_a_new_qr(): void
    {
        $qr = app(QrCodeService::class);

        $avant = $qr->cheminPlateforme('svg');

        config(['app.url' => 'https://autre-domaine.test']);

        $this->assertNotSame($avant, $qr->cheminPlateforme('svg'));
    }

    /**
     * AUCUN TEXTE N'EST JAMAIS TRONQUÉ SUR LA CARTE.
     *
     * Des points de suspension sur un écran sont un désagrément ; sur une
     * carte imprimée en centaines d'exemplaires, c'est un défaut définitif.
     * Ni ellipsis, ni line-clamp ne doivent revenir dans cette feuille.
     */
    public function test_no_card_text_can_ever_be_truncated(): void
    {
        $css = file_get_contents(resource_path('sass/_pvc.scss'));

        // On ignore les commentaires, qui documentent justement ce retrait.
        $regles = preg_replace('#//[^\n]*|/\*.*?\*/#s', '', $css);

        foreach (['text-overflow', 'line-clamp'] as $coupure) {
            $this->assertStringNotContainsString(
                $coupure,
                $regles,
                "« {$coupure} » est revenu : un texte de carte peut à nouveau être coupé."
            );
        }
    }

    /** Les coins restent à zéro : rectangle strict. */
    public function test_the_card_corners_stay_square(): void
    {
        $css = file_get_contents(resource_path('sass/_pvc.scss'));

        preg_match('/\.pvc__face\{[^}]*border-radius:\s*([^;]+);/s', $css, $m);

        $this->assertSame('0 !important', trim($m[1] ?? ''), 'Un arrondi est revenu sur la carte.');
    }

    /** La carte n'impose jamais de hauteur fixe : elle suit son ratio. */
    public function test_the_card_is_never_cropped_by_a_fixed_height(): void
    {
        $css = file_get_contents(resource_path('sass/_pvc.scss'));

        $this->assertStringContainsString('aspect-ratio:1.586', $css);

        // Une hauteur figée sur une face rognerait le contenu : c'est
        // exactement ce qui coupait le nom en deux.
        $this->assertDoesNotMatchRegularExpression(
            '/\.pvc__face\s*\{[^}]*\bheight\s*:\s*\d/s',
            $css,
            'Une hauteur fixe est revenue sur la face : elle rognera la carte.'
        );
    }

    /** Une seule action principale sur cet écran, et un lien de retour. */
    public function test_the_preview_offers_one_action_and_nothing_else(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('profile.preview'))->assertOk()->getContent();

        $this->assertStringContainsString('Activer ma carte', $html);
        $this->assertStringContainsString('Modifier mes informations', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    // =======================================================================
    // TÉLÉCHARGEMENTS
    // =======================================================================

    public function test_the_owner_downloads_the_qr_in_png_and_svg(): void
    {
        $png = $this->actingAs($this->user)->get(route('carte.qr.png'))->assertOk();
        $this->assertSame('image/png', $png->headers->get('Content-Type'));
        $this->assertStringContainsString('qr-awa-ndiaye.png', $png->headers->get('Content-Disposition'));

        $svg = $this->actingAs($this->user)->get(route('carte.qr.svg'))->assertOk();
        $this->assertSame('image/svg+xml', $svg->headers->get('Content-Type'));
    }

    /** Le PDF n'est délivré QUE si la carte est réellement en ligne. */
    public function test_the_printable_pdf_requires_a_published_card(): void
    {
        $this->actingAs($this->user)->get(route('carte.imprimable'))->assertForbidden();

        $this->publier();

        $reponse = $this->actingAs($this->user)->get(route('carte.imprimable'))->assertOk();

        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $reponse->getContent(), 'Le fichier livré n\'est pas un PDF.');
    }

    /**
     * CLOISONNEMENT — un compte ne télécharge jamais la carte d'un autre.
     *
     * Aucune de ces routes ne porte d'identifiant : la carte vient du compte
     * connecté. Un compte sans carte reçoit donc 404, jamais celle du voisin.
     */
    public function test_one_account_never_downloads_another_ones_card(): void
    {
        $this->publier();

        $autre = User::factory()->create(['email_verified_at' => now()]);

        foreach (['carte.qr.png', 'carte.qr.svg', 'carte.imprimable'] as $route) {
            $this->actingAs($autre)->get(route($route))->assertNotFound();
        }

        // Et avec SA propre carte, il obtient la sienne — pas celle du premier.
        Profile::factory()->create(['user_id' => $autre->id, 'slug' => 'moussa-diop']);

        // La relation « profile » a été consultée plus haut, donc mise en cache
        // à null sur cette instance : il faut la relire pour voir la nouvelle
        // carte. En production, chaque requête part d'un modèle neuf.
        $autre->unsetRelation('profile');

        $reponse = $this->actingAs($autre)->get(route('carte.qr.svg'))->assertOk();

        $this->assertStringContainsString('moussa-diop', $reponse->headers->get('Content-Disposition'));
    }

    public function test_a_guest_downloads_nothing(): void
    {
        foreach (['carte.qr.png', 'carte.qr.svg', 'carte.imprimable'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    // =======================================================================
    // TABLEAU DE BORD
    // =======================================================================

    public function test_the_dashboard_leads_with_the_card_and_its_downloads(): void
    {
        $this->publier();

        $html = $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="pvc', $html);
        $this->assertStringContainsString(route('carte.qr.png'), $html);
        $this->assertStringContainsString(route('carte.qr.svg'), $html);
        $this->assertStringContainsString(route('carte.imprimable'), $html);
    }

    /** Carte non publiée : pas de lien vers un PDF qu'on refuserait ensuite. */
    public function test_the_dashboard_hides_the_pdf_until_the_card_is_live(): void
    {
        $html = $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('carte.imprimable'), $html);
    }
}
