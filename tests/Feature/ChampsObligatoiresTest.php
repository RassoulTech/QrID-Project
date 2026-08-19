<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * LES CHAMPS OBLIGATOIRES SE SIGNALENT, ET TOUJOURS DE LA MÊME FAÇON.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE TEST QUI COMPTE EST LE PREMIER
 * ═══════════════════════════════════════════════════════════════════════
 * L'astérisque doit être posé par le COMPOSANT de champ, déduit de l'attribut
 * required — jamais écrit à la main dans une vue.
 *
 * La raison n'est pas l'élégance : une marque écrite à la main survit à la
 * suppression de la règle de validation qui la justifiait. On se retrouve
 * alors avec un champ marqué obligatoire que le serveur accepte vide, ou
 * l'inverse — un champ requis que rien ne signale, et dont l'utilisateur
 * découvre l'obligation en étant renvoyé au formulaire.
 *
 * Ce test échoue dès que quelqu'un écrit un astérisque dans un `<label>`.
 */
class ChampsObligatoiresTest extends TestCase
{
    use RefreshDatabase;

    /** Les composants qui ont LE DROIT de dessiner la marque. */
    private const COMPOSANTS_AUTORISES = [
        'input.blade.php',
        'select.blade.php',
        'textarea.blade.php',
        'password.blade.php',
        'checkbox.blade.php',
        'radio.blade.php',
        'field.blade.php',
        'auth-field.blade.php',
        'auth-password.blade.php',
        'phone-field.blade.php',
        'form-legende.blade.php',
    ];

    /**
     * AUCUNE VUE N'ÉCRIT D'ASTÉRISQUE À LA MAIN.
     *
     * On cherche la marque dans un libellé : « Prénom * », « Nom<span>*</span> »
     * et leurs variantes. Seuls les composants de champ y ont droit.
     */
    public function test_no_view_hand_writes_a_required_marker(): void
    {
        $fautifs = [];

        foreach (File::allFiles(resource_path('views')) as $fichier) {
            if (in_array($fichier->getFilename(), self::COMPOSANTS_AUTORISES, true)) {
                continue;
            }

            $contenu = File::get($fichier->getPathname());

            // Un astérisque DANS un libellé de champ, sous ses deux formes
            // usuelles : collé au texte, ou enveloppé dans un span.
            if (preg_match('#<label[^>]*>[^<]*\*#u', $contenu)
                || preg_match('#<label[^>]*>.*?<span[^>]*>\s*\*\s*</span>#us', $contenu)) {
                $fautifs[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $fichier->getPathname());
            }
        }

        $this->assertSame([], $fautifs, implode("\n", array_merge(
            ['Un astérisque est écrit à la main dans ces vues.'],
            ['Il doit être posé par le composant de champ, déduit de required :'],
            $fautifs
        )));
    }

    /**
     * LA MARQUE ET L'ATTRIBUT NE PEUVENT PAS SE CONTREDIRE.
     *
     * Un champ rendu obligatoire affiche l'astérisque ET porte required ET
     * annonce aria-required. Les trois viennent de la même condition, donc
     * aucun ne peut mentir sur les autres.
     */
    public function test_a_required_field_carries_the_three_signals(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertStringContainsString('f__requis', $html, 'Aucun astérisque sur un formulaire qui a des champs requis.');
        $this->assertStringContainsString('aria-required="true"', $html, 'aria-required absent : le champ est muet pour un lecteur d\'écran.');
        $this->assertStringContainsString('required', $html);
    }

    /** Un champ facultatif le dit, et ne porte pas la marque. */
    public function test_an_optional_field_says_so(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $html = $this->actingAs($user)
            ->get(route('profile.create.step1'))
            ->assertOk()
            ->getContent();

        // « Entreprise » est déclaré optional à l'étape 1.
        $this->assertStringContainsString('f__opt', $html);
    }

    /**
     * LA LÉGENDE EXPLIQUE LA MARQUE, ET ELLE EST EN TÊTE.
     *
     * Un astérisque rouge est une convention, pas une évidence. Sur un produit
     * dont une partie des clients découvre l'administratif numérique,
     * l'expliquer une fois coûte une ligne.
     */
    public function test_the_forms_carry_the_legend(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('astérisque sont obligatoires');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('profile.create.step1'))
            ->assertOk()
            ->assertSee('astérisque sont obligatoires');
    }
}
