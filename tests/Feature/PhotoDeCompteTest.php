<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * LA PHOTO DE COMPTE — celle qui remplace les initiales.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ELLE N'EST PAS LA PHOTO DE LA CARTE, ET C'EST LE POINT LE PLUS IMPORTANT
 * ═══════════════════════════════════════════════════════════════════════════
 * La carte publique a UNE image : la couverture, choisie dans l'assistant,
 * vue par les prospects. Celle-ci est l'avatar de l'espace client, que seul
 * son porteur voit.
 *
 * Les confondre priverait d'un choix légitime — un bandeau soigné sur la
 * carte commerciale, sa propre tête dans son espace. Et surtout, un client
 * qui importe ici sa photo en s'attendant à la voir sur sa carte conclurait
 * que le téléversement n'a pas fonctionné.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE REPLI EST LE CAS NORMAL
 * ═══════════════════════════════════════════════════════════════════════════
 * « MD », « AD » : deux lettres sur un rond. La plupart des comptes n'auront
 * jamais autre chose, et c'est très bien — un avatar n'est pas ce pour quoi
 * on achète une carte de visite.
 */
class PhotoDeCompteTest extends TestCase
{
    use RefreshDatabase;

    private function client(array $attributs = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Mouhamed Dione',
            'email_verified_at' => now(),
        ], $attributs));
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->image('moi.jpg', 600, 400);
    }

    // =======================================================================
    // LES INITIALES, PAR DÉFAUT
    // =======================================================================

    public function test_initials_come_from_the_first_two_words_of_the_name(): void
    {
        $this->assertSame('MD', $this->client()->initiales());
        $this->assertSame('AN', $this->client(['name' => 'Awa Ndiaye'])->initiales());
    }

    /** Un prénom seul donne une seule lettre, jamais une erreur. */
    public function test_a_single_word_name_still_yields_initials(): void
    {
        $this->assertSame('A', $this->client(['name' => 'Awa'])->initiales());
    }

    /** Sans photo ni compte Google, il n'y a pas d'adresse à donner. */
    public function test_without_a_photo_there_is_no_avatar_url(): void
    {
        $this->assertNull($this->client()->avatarUrl());
    }

    // =======================================================================
    // L'IMPORT
    // =======================================================================

    public function test_a_client_can_upload_an_account_photo(): void
    {
        Storage::fake('public');
        $client = $this->client();

        $this->actingAs($client)
            ->post(route('compte.avatar.update'), ['avatar' => $this->image()])
            ->assertRedirect();

        $client->refresh();

        $this->assertNotNull($client->avatar_path);
        $this->assertNotNull($client->avatarUrl());
    }

    /**
     * LES OCTETS SONT EN BASE, PAS SEULEMENT SUR LE DISQUE.
     *
     * Le disque du conteneur est recréé à chaque déploiement. Un avatar qui
     * n'y vivrait que là disparaîtrait à la première mise en ligne, et le
     * client conclurait que le produit ne sait pas garder une image.
     */
    public function test_the_photo_survives_a_wiped_disk(): void
    {
        Storage::fake('public');
        $client = $this->client();

        $this->actingAs($client)->post(route('compte.avatar.update'), ['avatar' => $this->image()]);

        $client->refresh();
        $this->assertNotNull($client->avatar_data, 'Les octets ne sont pas en base.');

        // Le déploiement : le disque repart à zéro.
        Storage::fake('public');

        $this->assertNotNull($client->avatarBinaire(),
            "L'avatar a disparu avec le disque alors qu'il est conservé en base.");
    }

    /** Une image trop lourde est refusée avec un message, pas un plantage. */
    public function test_an_oversized_file_is_refused(): void
    {
        Storage::fake('public');
        $client = $this->client();

        $this->actingAs($client)
            ->post(route('compte.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('enorme.jpg', 5000, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($client->refresh()->avatar_path);
    }

    /** Un fichier qui n'est pas une image est refusé. */
    public function test_a_non_image_is_refused(): void
    {
        Storage::fake('public');
        $client = $this->client();

        $this->actingAs($client)
            ->post(route('compte.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('contrat.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('avatar');
    }

    // =======================================================================
    // LE RETOUR AUX INITIALES
    // =======================================================================

    public function test_a_client_can_go_back_to_their_initials(): void
    {
        Storage::fake('public');
        $client = $this->client();

        $this->actingAs($client)->post(route('compte.avatar.update'), ['avatar' => $this->image()]);
        $this->assertNotNull($client->refresh()->avatar_path);

        $this->actingAs($client)->delete(route('compte.avatar.destroy'))->assertRedirect();

        $client->refresh();
        $this->assertNull($client->avatar_path);
        $this->assertNull($client->avatar_data);
        $this->assertNull($client->avatarUrl());
    }

    // =======================================================================
    // LE CLOISONNEMENT
    // =======================================================================

    /** Un visiteur non connecté ne peut rien importer. */
    public function test_a_guest_cannot_upload(): void
    {
        Storage::fake('public');

        $this->post(route('compte.avatar.update'), ['avatar' => $this->image()])
            ->assertRedirect(route('login'));
    }

    /**
     * L'IMPORT NE TOUCHE QUE LE COMPTE CONNECTÉ.
     *
     * La photo est écrite sur `$request->user()`, jamais sur un identifiant
     * reçu de l'extérieur — c'est ce qui rend l'usurpation impossible sans
     * qu'aucune vérification n'ait à être écrite.
     */
    public function test_an_upload_never_touches_another_account(): void
    {
        Storage::fake('public');

        $client = $this->client();
        $voisin = $this->client(['name' => 'Awa Ndiaye']);

        $this->actingAs($client)->post(route('compte.avatar.update'), ['avatar' => $this->image()]);

        $this->assertNull($voisin->refresh()->avatar_path);
    }

    // =======================================================================
    // LA SÉPARATION AVEC LA CARTE
    // =======================================================================

    /**
     * LA PAGE COMPTE DIT QUE CETTE PHOTO NE SORT PAS DE L'ESPACE CLIENT.
     *
     * Sans cette phrase, quelqu'un qui importe ici sa photo l'attendrait sur
     * sa carte publique, ne l'y verrait pas, et conclurait à une panne.
     */
    public function test_the_account_page_says_this_photo_stays_private(): void
    {
        $client = $this->client();

        $this->actingAs($client)
            ->get(route('compte.edit'))
            ->assertOk()
            ->assertSee(__('profile.compte.avatar_carte_sous'));
    }
}
