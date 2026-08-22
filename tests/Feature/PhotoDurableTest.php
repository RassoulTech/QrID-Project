<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Services\VCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * LA PHOTO SURVIT AU DÉPLOIEMENT.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE DÉFAUT, ET POURQUOI IL A MIS DU TEMPS À SE VOIR
 * ═══════════════════════════════════════════════════════════════════════
 * Le conteneur de production a un disque ÉPHÉMÈRE : il est reconstruit à
 * chaque déploiement. La colonne photo_path, elle, est en base et survit.
 * Le profil gardait donc un chemin qui ne menait plus nulle part.
 *
 * En local, rien ne se voyait jamais : le disque persiste. En production,
 * la photo s'affichait quelques heures — le temps du déploiement suivant —
 * puis la page publique retombait sur les initiales, l'aperçu de partage
 * perdait le portrait et la fiche contact partait sans photo. Trois
 * symptômes, une seule cause, et aucun message d'erreur nulle part.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA BASE EST LA SOURCE, LE DISQUE EST UN CACHE
 * ═══════════════════════════════════════════════════════════════════════
 * Les octets sont écrits dans photo_data. photoBinaire() lit le disque
 * quand il les a encore, la base sinon — et REMET le fichier en place au
 * passage, pour que les requêtes suivantes soient servies par le disque.
 *
 * Ces tests effacent le disque SANS toucher à la base : c'est exactement
 * ce que fait un déploiement.
 */
class PhotoDurableTest extends TestCase
{
    use RefreshDatabase;

    /** Une petite image réelle, pas une chaîne quelconque. */
    private function octets(): string
    {
        $image = imagecreatetruecolor(24, 24);
        imagefill($image, 0, 0, imagecolorallocate($image, 12, 90, 60));

        ob_start();
        imagejpeg($image, null, 80);

        return (string) ob_get_clean();
    }

    private function profilAvecPhoto(): Profile
    {
        Storage::fake('public');

        $profile = Profile::factory()->create(['photo_path' => 'photos/test.jpg']);
        $profile->forceFill(['photo_data' => $this->octets()])->save();

        return $profile->fresh();
    }

    /**
     * LE DISQUE EST PRIVILÉGIÉ TANT QU'IL A LE FICHIER.
     *
     * Lire la base à chaque requête ferait transiter l'image par PHP alors
     * que le serveur web sait la servir seul.
     */
    public function test_the_disk_is_read_first_when_it_still_has_the_file(): void
    {
        $profile = $this->profilAvecPhoto();

        Storage::disk('public')->put('photos/test.jpg', 'octets-du-disque');

        $this->assertSame('octets-du-disque', $profile->photoBinaire());
    }

    /** LE DÉPLOIEMENT EFFACE LE DISQUE — la photo reste. */
    public function test_the_photo_survives_a_wiped_disk(): void
    {
        $profile = $this->profilAvecPhoto();

        Storage::disk('public')->delete('photos/test.jpg');

        $this->assertTrue($profile->aUnePhoto());
        $this->assertSame($this->octets(), $profile->photoBinaire());
    }

    /**
     * LE FICHIER EST REMIS EN PLACE À LA PREMIÈRE LECTURE.
     *
     * Sans cela, chaque visiteur ferait relire la colonne binaire, et la
     * balise <img> de la page publique — qui pointe vers /storage — répondrait
     * toujours 404 puisque personne n'aurait jamais réécrit le fichier.
     */
    public function test_the_first_read_rebuilds_the_cached_file(): void
    {
        $profile = $this->profilAvecPhoto();

        Storage::disk('public')->delete('photos/test.jpg');
        Storage::disk('public')->assertMissing('photos/test.jpg');

        $profile->photoBinaire();

        Storage::disk('public')->assertExists('photos/test.jpg');
    }

    /** SANS PHOTO, aucune invention : l'appelant doit pouvoir replier. */
    public function test_a_profile_without_a_photo_reports_none(): void
    {
        Storage::fake('public');

        $profile = Profile::factory()->create(['photo_path' => null]);

        $this->assertFalse($profile->aUnePhoto());
        $this->assertNull($profile->photoBinaire());
    }

    /**
     * LA FICHE CONTACT EMBARQUE LE PORTRAIT APRÈS UN DÉPLOIEMENT.
     *
     * VCardService testait l'EXISTENCE du fichier : toutes les fiches
     * enregistrées après un déploiement partaient sans photo, alors que
     * l'image était bel et bien conservée.
     */
    public function test_the_vcard_still_embeds_the_photo_after_a_deploy(): void
    {
        $profile = $this->profilAvecPhoto();

        Storage::disk('public')->delete('photos/test.jpg');

        $this->assertStringContainsString(
            'PHOTO;ENCODING=b;TYPE=JPEG:',
            app(VCardService::class)->pour($profile)
        );
    }
}
