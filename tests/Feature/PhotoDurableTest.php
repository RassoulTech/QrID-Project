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
 * chaque déploiement. La colonne cover_path, elle, est en base et survit.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE FICHIER PORTAIT SUR LE PORTRAIT. IL PORTE SUR LA COUVERTURE.
 * ═══════════════════════════════════════════════════════════════════════════
 * Le produit demandait autrefois deux images. Il n'en demande plus qu'une, et
 * c'est la couverture : elle occupe le bandeau de la carte, et c'est la seule
 * que le porteur ait jamais l'occasion de choisir.
 *
 * Le mécanisme testé ici — disque d'abord, base ensuite, cache reconstruit au
 * passage — n'a pas changé d'un octet. Seule la colonne qu'il protège a
 * changé de nom, et la garantie compte autant : une carte qui perd son image
 * au déploiement est une carte que son porteur croit cassée.
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
 * Les octets sont écrits dans cover_data. couvertureBinaire() lit le disque
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

        $profile = Profile::factory()->create(['cover_path' => 'couvertures/test.jpg']);
        $profile->forceFill(['cover_data' => $this->octets()])->save();

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

        Storage::disk('public')->put('couvertures/test.jpg', 'octets-du-disque');

        $this->assertSame('octets-du-disque', $profile->couvertureBinaire());
    }

    /** LE DÉPLOIEMENT EFFACE LE DISQUE — la photo reste. */
    public function test_the_photo_survives_a_wiped_disk(): void
    {
        $profile = $this->profilAvecPhoto();

        Storage::disk('public')->delete('couvertures/test.jpg');

        $this->assertTrue($profile->aUneCouverture());
        $this->assertSame($this->octets(), $profile->couvertureBinaire());
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

        Storage::disk('public')->delete('couvertures/test.jpg');
        Storage::disk('public')->assertMissing('couvertures/test.jpg');

        $profile->couvertureBinaire();

        Storage::disk('public')->assertExists('couvertures/test.jpg');
    }

    /** SANS PHOTO, aucune invention : l'appelant doit pouvoir replier. */
    public function test_a_profile_without_a_photo_reports_none(): void
    {
        Storage::fake('public');

        $profile = Profile::factory()->create(['cover_path' => null]);

        $this->assertFalse($profile->aUneCouverture());
        $this->assertNull($profile->couvertureBinaire());
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

        Storage::disk('public')->delete('couvertures/test.jpg');

        $this->assertStringContainsString(
            'PHOTO;ENCODING=b;TYPE=JPEG:',
            app(VCardService::class)->pour($profile)
        );
    }
}
