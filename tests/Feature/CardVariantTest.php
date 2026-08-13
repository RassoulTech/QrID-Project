<?php

namespace Tests\Feature;

use App\Enums\VarianteCarte;
use App\Models\Profile;
use App\Models\Template;
use App\Models\User;
use App\Services\CardTextureService;
use App\Services\PrintableCardService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * DEUX VARIANTES DE CARTE, ET SEULEMENT DEUX.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS PROTÈGENT VRAIMENT
 * ═══════════════════════════════════════════════════════════════════════
 * Pas une préférence esthétique : une contrainte de MARQUE. Chaque carte
 * imprimée est un support de communication pour la plateforme. Cinq teintes
 * au choix produisaient cinq marques — celui qui reçoit une carte ambre et
 * une carte grenat ne voit pas deux clients d'un même service, il voit deux
 * services.
 *
 * Le défaut que ces tests empêchent est particulier : il ne casse rien, ne
 * lève aucune erreur, et ne se constate que sur des cartes déjà sorties de
 * l'imprimerie. C'est exactement le genre de régression qu'un test doit
 * attraper, parce que rien d'autre ne l'attrapera.
 */
class CardVariantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();

        $this->profile = Profile::factory()->for($this->user)->create([
            'slug' => 'awa-ndiaye',
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Architecte',
            'primary_color' => VarianteCarte::Verte->value,
            'is_active' => true,
        ]);
    }

    // =======================================================================
    // L'ENSEMBLE EST FERMÉ
    // =======================================================================

    public function test_exactly_two_variants_exist(): void
    {
        $this->assertCount(
            2,
            VarianteCarte::cases(),
            'Une troisième variante est apparue : chaque carte imprimée est un support de marque.'
        );
    }

    /**
     * Une teinte de l'ANCIEN nuancier ne lève pas — elle retombe sur la
     * variante par défaut.
     *
     * Tolérance nécessaire, et non facilité : des profils portent encore
     * « #7A3E12 » en base. Une exception ici casserait la page publique d'un
     * client, c'est-à-dire la seule page que voient ses propres contacts.
     */
    public function test_a_legacy_colour_degrades_instead_of_throwing(): void
    {
        $this->assertSame(VarianteCarte::DEFAUT, VarianteCarte::depuis('#7A3E12'));
        $this->assertSame(VarianteCarte::DEFAUT, VarianteCarte::depuis(null));
        $this->assertSame(VarianteCarte::DEFAUT, VarianteCarte::depuis(''));
    }

    /** La casse ne doit pas décider d'une variante. */
    public function test_the_lookup_is_case_insensitive(): void
    {
        $this->assertSame(VarianteCarte::Blanche, VarianteCarte::depuis('#ffffff'));
    }

    /**
     * LE FORMULAIRE REFUSE TOUTE AUTRE VALEUR.
     *
     * C'est le premier des deux bouts : rien d'autre que ces deux valeurs ne
     * peut ENTRER en base, quoi qu'on poste. Le second bout est la résolution
     * tolérante en sortie.
     */
    public function test_a_colour_outside_the_two_variants_is_refused(): void
    {
        $candidat = $this->candidat();

        $this->deuxPremieresEtapes($candidat);

        $this->actingAs($candidat)
            ->post(route('profile.store.step3'), [
                'template_id' => $this->gabarit()->id,
                'primary_color' => '#7A3E12',   // ancienne teinte « Ambre »
            ])
            ->assertSessionHasErrors('primary_color');
    }

    // =======================================================================
    // LE QR SUIT LA VARIANTE — MAIS PAS LE FICHIER TÉLÉCHARGEABLE
    // =======================================================================

    /**
     * Le code IMPRIMÉ SUR LA CARTE change avec la variante : modules blancs
     * sur la verte, vert profond sur la blanche.
     */
    public function test_the_printed_qr_follows_the_variant(): void
    {
        $qr = app(QrCodeService::class);

        $vert = $qr->carteSvg($this->profile);

        $this->profile->forceFill(['primary_color' => VarianteCarte::Blanche->value])->save();

        $blanc = $qr->carteSvg($this->profile->refresh());

        $this->assertNotSame(
            $vert,
            $blanc,
            'Les deux variantes produisent le même QR : l\'un des deux sera invisible sur sa carte.'
        );
    }

    /**
     * LE FICHIER TÉLÉCHARGEABLE, LUI, NE CHANGE JAMAIS.
     *
     * Il part chez un imprimeur ou dans une signature de courriel : il doit
     * être lisible partout. Le colorer avec le fond de la variante donnerait,
     * pour la blanche, un code blanc sur blanc — invisible, et découvert trop
     * tard.
     */
    public function test_the_downloadable_qr_ignores_the_variant(): void
    {
        $qr = app(QrCodeService::class);

        $avant = $qr->svg($this->profile);

        $this->profile->forceFill(['primary_color' => VarianteCarte::Blanche->value])->save();

        $this->assertSame($avant, $qr->svg($this->profile->refresh()));
    }

    /**
     * Changer de variante régénère le fichier de carte.
     *
     * L'observateur écoute primary_color. Sans lui, la carte garderait le QR
     * de l'ancienne variante jusqu'à la prochaine modification du slug — donc
     * potentiellement jusqu'à l'impression.
     */
    public function test_switching_variant_regenerates_the_card_files(): void
    {
        $disque = Storage::disk('public');

        $disque->assertExists('qr/awa-ndiaye.carte-Verte.svg');

        $this->profile->forceFill(['primary_color' => VarianteCarte::Blanche->value])->save();

        $disque->assertExists('qr/awa-ndiaye.carte-Blanche.svg');
        $disque->assertMissing('qr/awa-ndiaye.carte-Verte.svg');
    }

    // =======================================================================
    // LE PARCOURS NE PROPOSE PLUS DE COULEUR
    // =======================================================================

    /**
     * L'étape 3 montre DEUX CARTES, pas un nuancier.
     *
     * La différence de présentation est le fond du sujet : des pastilles
     * invitent à composer une identité personnelle, deux aperçus de carte
     * invitent à choisir entre deux objets finis.
     */
    public function test_the_style_step_offers_two_cards_and_no_swatches(): void
    {
        $this->gabarit();

        $candidat = $this->candidat();
        $this->deuxPremieresEtapes($candidat);

        $html = $this->actingAs($candidat)
            ->get(route('profile.create.step3'))
            ->assertOk()
            ->getContent();

        // Les deux variantes sont proposées, nommées.
        foreach (VarianteCarte::cases() as $variante) {
            $this->assertStringContainsString($variante->value, $html);
            $this->assertStringContainsString($variante->libelle(), $html);
        }

        // Le nuancier a disparu — classes et module JavaScript compris.
        $this->assertStringNotContainsString('swatch', $html);
        $this->assertStringNotContainsString('data-color-preview', $html);

        // Et aucune des anciennes teintes ne subsiste dans la page.
        foreach (['#7A3E12', '#0E5F73', '#8C1D18', '#0F172A'] as $ancienne) {
            $this->assertStringNotContainsString($ancienne, $html);
        }
    }

    /**
     * Le champ de couleur libre n'existe nulle part.
     *
     * Un <input type="color"> réintroduirait, à lui seul, tout ce que cette
     * décision supprime.
     */
    public function test_no_free_colour_input_exists_anywhere(): void
    {
        $vues = glob(resource_path('views/**/*.blade.php')) ?: [];
        $vues = array_merge($vues, glob(resource_path('views/*/*/*.blade.php')) ?: []);

        foreach ($vues as $fichier) {
            $this->assertStringNotContainsString(
                'type="color"',
                (string) file_get_contents($fichier),
                'Un sélecteur de couleur libre est réapparu dans '.basename($fichier)
            );
        }
    }

    // =======================================================================
    // LES QUATRE FACES SE FABRIQUENT
    // =======================================================================

    /** Le fond organique existe pour chaque variante, et c'est bien un PNG. */
    public function test_the_organic_background_is_produced_for_both_variants(): void
    {
        $texture = app(CardTextureService::class);

        foreach (VarianteCarte::cases() as $variante) {
            $png = $texture->png($variante);

            $this->assertStringStartsWith("\x89PNG", $png, 'Le fond de la variante '.$variante->name.' n\'est pas un PNG.');
            Storage::disk('public')->assertExists("cartes/fond-{$variante->name}.png");
        }
    }

    /**
     * Le PDF d'impression se produit pour les DEUX variantes.
     *
     * Test lent — deux rendus DomPDF — et assumé : c'est le seul fichier du
     * projet qui parte chez un imprimeur. Une erreur découverte ici coûte
     * trente secondes ; découverte après tirage, elle coûte le tirage.
     */
    public function test_the_printable_pdf_builds_for_both_variants(): void
    {
        $carte = app(PrintableCardService::class);

        foreach (VarianteCarte::cases() as $variante) {
            $this->profile->forceFill(['primary_color' => $variante->value])->save();

            $pdf = $carte->render($this->profile->refresh());

            $this->assertStringStartsWith('%PDF', $pdf, 'Le PDF de la variante '.$variante->name.' est invalide.');
            $this->assertGreaterThan(5000, strlen($pdf), 'PDF suspicieusement léger : une image manque probablement.');
        }
    }

    // =======================================================================

    /**
     * Le parcours EST traversé, il n'est pas simulé en session.
     *
     * Injecter directement l'état du parcours ferait passer le middleware de
     * séquence sans jamais l'exercer — et un test qui contourne la garde
     * qu'il devrait éprouver ne prouve rien.
     */
    private function deuxPremieresEtapes(User $compte): void
    {
        $this->actingAs($compte)->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante',
        ]);

        $this->actingAs($compte)->post(route('profile.store.step2'), [
            'phone' => '770000000',
        ]);
    }

    /**
     * Un compte SANS carte.
     *
     * Celui de setUp() en possède une : le parcours de création le renverrait
     * alors vers l'édition, et l'écran de style ne s'afficherait jamais. Ce
     * n'est pas un défaut du produit, c'est la règle « un compte, une carte ».
     */
    private function candidat(): User
    {
        return User::factory()->create();
    }

    private function gabarit(): Template
    {
        return Template::query()->where('is_active', true)->first()
            ?? Template::factory()->create(['is_active' => true]);
    }
}
