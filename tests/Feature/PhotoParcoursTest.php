<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Template;
use App\Models\User;
use App\Services\ProfileWizardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * LES IMAGES TÉLÉVERSÉES ARRIVENT JUSQU'EN BASE — le parcours entier.
 *
 * PhotoDurableTest vérifie le MODÈLE : octets en base, disque reconstitué.
 * Il ne dit rien du CHEMIN qui mène ces octets jusque-là — formulaire,
 * requête validée, session, brouillon, finalisation. C'est ce chemin qui a
 * lâché : la photo se téléversait, le fichier se déposait, et la page
 * publique affichait toujours les initiales.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS N'EXIGENT PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Ils ne disent RIEN de la façon dont les octets voyagent. Ils avaient
 * d'abord vérifié leur présence en session — et se seraient donc mis à
 * échouer le jour où ce détour a été supprimé, alors que le produit
 * s'améliorait. Un test qui interdit une simplification est un test qui
 * décrit du code au lieu de décrire un comportement.
 *
 * Ce qui compte, et ce qui est vérifié : à la fin du parcours, le profil
 * porte ses octets.
 */
class PhotoParcoursTest extends TestCase
{
    use RefreshDatabase;

    /** Mène le parcours jusqu'au bout et rend le profil créé. */
    private function parcours(User $user, array $fichiers): ?Profile
    {
        $this->actingAs($user);

        $this->post(route('profile.store.step1'), array_merge([
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Architecte',
        ], $fichiers))->assertSessionHasNoErrors();

        $this->post(route('profile.store.step2'), [
            'phone' => '77 383 13 64',
            'phone_pays' => 'SN',
            'public_email' => 'awa@exemple.sn',
        ])->assertSessionHasNoErrors();

        $this->post(route('profile.store.step3'), [
            'template_id' => Template::query()->where('is_active', true)->value('id')
                ?? Template::factory()->create(['is_active' => true])->id,
            'primary_color' => '#0B3B2E',
        ])->assertSessionHasNoErrors();

        return Profile::where('user_id', $user->id)->first();
    }

    /**
     * IL N'Y A PLUS DE CHAMP PHOTO, ET C'EST VOULU.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CE TEST A REMPLACÉ CELUI QUI VÉRIFIAIT LE PORTRAIT
     * ═══════════════════════════════════════════════════════════════════
     * La page publique montrait un médaillon rond sur une bannière : deux
     * images à fournir. Le porteur en donnait rarement deux, et sa carte
     * affichait donc le plus souvent des initiales sur un dégradé — deux
     * replis empilés, c'est-à-dire un vide décoré.
     *
     * Une seule image désormais, qui occupe le haut de la page et porte le
     * nom. Ce test garde le champ supprimé : le remettre ferait revenir la
     * question « laquelle des deux dois-je fournir ? », qui n'a jamais eu
     * de bonne réponse.
     */
    public function test_the_photo_field_is_gone_from_the_journey(): void
    {
        $vue = (string) file_get_contents(resource_path('views/profile/wizard/step-1.blade.php'));

        $this->assertStringNotContainsString('name="photo"', $vue, 'Le champ photo est revenu.');
        $this->assertStringContainsString('name="cover"', $vue, "Le champ d'image de couverture a disparu.");

        $regles = (new \App\Http\Requests\Profile\WizardStepOneRequest)->rules();

        $this->assertArrayNotHasKey('photo', $regles);
        $this->assertArrayHasKey('cover', $regles);
    }

    /**
     * LA BANNIÈRE SUIT LE MÊME CHEMIN.
     *
     * Elle est facultative, mais quand elle est là elle doit être aussi
     * durable que le portrait : c'est la zone la plus visible de la carte.
     */
    public function test_the_cover_reaches_the_database(): void
    {
        Storage::fake('public');

        $profile = $this->parcours(
            User::factory()->create(['email_verified_at' => now()]),
            ['cover' => UploadedFile::fake()->image('banniere.jpg', 1600, 600)]
        );

        $this->assertNotNull($profile);
        $this->assertNotEmpty($profile->cover_path);
        $this->assertNotEmpty($profile->cover_data);
        $this->assertTrue($profile->aUneCouverture());
    }

    /** SANS BANNIÈRE, rien n'est écrit — et rien n'échoue. */
    public function test_the_cover_stays_optional(): void
    {
        Storage::fake('public');

        $profile = $this->parcours(
            User::factory()->create(['email_verified_at' => now()]),
            []
        );

        $this->assertNotNull($profile);
        $this->assertNull($profile->cover_path);
        $this->assertFalse($profile->aUneCouverture());
    }

    /**
     * LA PHOTO EST ÉCRITE DÈS L'ÉTAPE 1, SANS ALLER JUSQU'AU BOUT.
     *
     * ═══════════════════════════════════════════════════════════════════
     * LE DÉFAUT QUE CE TEST GARDE FERMÉ
     * ═══════════════════════════════════════════════════════════════════
     * Le profil n'était écrit qu'à l'étape 3. Quelqu'un qui ouvrait
     * « Modifier », déposait sa photo, la voyait apparaître dans la vignette
     * et refermait l'onglet — parce que tout semblait fait — ne changeait
     * rien du tout. Rien n'échouait, rien n'était signalé.
     *
     * Ce test s'arrête VOLONTAIREMENT après l'étape 1 : c'est le geste réel
     * du client, et c'est celui qui doit suffire.
     */
    public function test_an_edit_saves_the_cover_at_step_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $profile = $this->parcours($user, []);
        $this->assertEmpty($profile->cover_data);

        app(ProfileWizardService::class)->hydrateFrom($profile->fresh());

        $this->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Architecte',
            'cover' => UploadedFile::fake()->image('banniere.jpg', 1600, 900),
        ])->assertSessionHasNoErrors();

        // On NE VA PAS jusqu'à l'étape 3 : c'est tout le sujet.
        $apres = $profile->fresh();

        $this->assertNotEmpty($apres->cover_path, "Le chemin n'a pas été écrit.");
        $this->assertNotEmpty(
            $apres->cover_data,
            "L'image n'est enregistrée qu'au bout du parcours : celui qui referme avant la perd."
        );
    }

    /**
     * MODIFIER SON NOM N'EFFACE PAS SON IMAGE.
     *
     * persist() écrit toutes les colonnes à chaque passage. Sans le repli sur
     * l'existant, une correction de faute de frappe remettrait cover_data à
     * null — et la photo repartirait pour de bon au déploiement suivant.
     */
    public function test_editing_without_re_uploading_keeps_the_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $profile = $this->parcours($user, [
            'cover' => UploadedFile::fake()->image('banniere.jpg', 1600, 600),
        ]);

        $this->assertNotEmpty($profile->cover_data);

        // Le parcours d'édition est le parcours de création : même écrans,
        // mêmes règles. Voir ProfileWizardService::hydrateFrom().
        app(ProfileWizardService::class)->hydrateFrom($profile->fresh());

        $this->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye-Fall',
            'job_title' => 'Architecte',
        ])->assertSessionHasNoErrors();

        $this->post(route('profile.store.step3'), [
            'template_id' => Template::query()->where('is_active', true)->value('id')
                ?? Template::factory()->create(['is_active' => true])->id,
            'primary_color' => '#0B3B2E',
        ])->assertSessionHasNoErrors();

        $apres = $profile->fresh();

        $this->assertSame('Ndiaye-Fall', $apres->last_name);
        $this->assertNotEmpty($apres->cover_data, "Une simple correction de nom a effacé l'image.");
        $this->assertNotEmpty($apres->cover_data, 'Une simple correction de nom a effacé la bannière.');
    }
}
