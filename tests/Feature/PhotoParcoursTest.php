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

    public function test_the_photo_reaches_the_database(): void
    {
        Storage::fake('public');

        $profile = $this->parcours(
            User::factory()->create(['email_verified_at' => now()]),
            ['photo' => UploadedFile::fake()->image('portrait.jpg', 800, 800)]
        );

        $this->assertNotNull($profile, 'Le profil n\'a pas été créé.');
        $this->assertNotEmpty($profile->photo_path);
        $this->assertNotEmpty(
            $profile->photo_data,
            'La photo est sur le disque mais PAS en base : elle disparaîtra au prochain déploiement.'
        );
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
     * MODIFIER SON NOM N'EFFACE PAS SA PHOTO.
     *
     * persist() écrit toutes les colonnes à chaque passage. Sans le repli sur
     * l'existant, une correction de faute de frappe remettrait photo_data à
     * null — et la photo repartirait pour de bon au déploiement suivant.
     */
    public function test_editing_without_re_uploading_keeps_the_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $profile = $this->parcours($user, [
            'photo' => UploadedFile::fake()->image('portrait.jpg', 800, 800),
            'cover' => UploadedFile::fake()->image('banniere.jpg', 1600, 600),
        ]);

        $this->assertNotEmpty($profile->photo_data);

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
        $this->assertNotEmpty($apres->photo_data, 'Une simple correction de nom a effacé la photo.');
        $this->assertNotEmpty($apres->cover_data, 'Une simple correction de nom a effacé la bannière.');
    }
}
