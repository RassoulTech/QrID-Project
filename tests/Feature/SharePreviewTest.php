<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SharePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * L'APERÇU DE PARTAGE — l'image qui s'affiche quand on colle un lien.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'IL PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════
 * Le geste central de QrID est de coller son lien dans WhatsApp. Sans balise
 * og:image, WhatsApp rend un aperçu minuscule : une ligne de titre grise et
 * rien d'autre. Avec, il rend une grande vignette qu'on remarque dans une
 * conversation.
 *
 * L'écart n'est pas cosmétique : c'est la différence entre un lien qu'on
 * ouvre et un lien qu'on fait défiler. Le produit tout entier repose sur ce
 * partage, et il se faisait jusqu'ici sans image.
 */
class SharePreviewTest extends TestCase
{
    use RefreshDatabase;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $plan = Plan::factory()->create(['slug' => 'standard', 'price_fcfa' => 2500, 'duration_days' => 30]);
        $user = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);

        $this->profile = Profile::factory()->for($user)->create([
            'slug' => 'awa-ndiaye',
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante en gestion',
            'company' => 'Teranga Conseil',
            'photo_path' => null,
            'is_active' => true,
        ]);
    }

    // =======================================================================
    // L'IMAGE
    // =======================================================================

    /** Une image est produite, au format attendu par les messageries. */
    public function test_a_preview_image_is_produced_at_the_right_size(): void
    {
        $png = app(SharePreviewService::class)->png($this->profile);

        $this->assertStringStartsWith("\x89PNG", $png);

        $taille = getimagesizefromstring($png);

        // 1200 × 630 = 1,91:1, le format imposé par WhatsApp, Facebook et
        // LinkedIn. Un autre ratio serait recadré, souvent au mauvais endroit.
        $this->assertSame(1200, $taille[0]);
        $this->assertSame(630, $taille[1]);
    }

    /**
     * ELLE EXISTE MÊME SANS PHOTO.
     *
     * C'est la raison pour laquelle on ne se contente pas de la photo du
     * profil : la moitié des cartes n'en ont pas, et ces partages-là
     * retomberaient sans image.
     */
    public function test_a_preview_exists_even_without_a_photo(): void
    {
        $this->assertNull($this->profile->photo_path);

        $png = app(SharePreviewService::class)->png($this->profile);

        $this->assertGreaterThan(5000, strlen($png), 'Image suspicieusement légère : elle est probablement vide.');
    }

    /** Écrite une fois, relue ensuite : la page publique ne la repeint pas. */
    public function test_the_image_is_cached(): void
    {
        $service = app(SharePreviewService::class);

        $service->png($this->profile);

        Storage::disk('public')->assertExists($service->chemin($this->profile));
    }

    /**
     * CORRIGER SA FONCTION REFAIT L'APERÇU.
     *
     * Sans cette régénération, l'ancienne fonction s'afficherait dans tous les
     * partages, indéfiniment — l'image n'étant relue que si son nom de fichier
     * change.
     */
    public function test_changing_the_job_title_produces_a_new_image(): void
    {
        $service = app(SharePreviewService::class);

        $avant = $service->chemin($this->profile);

        $this->profile->forceFill(['job_title' => 'Urbaniste'])->save();

        $this->assertNotSame($avant, $service->chemin($this->profile->refresh()));
    }

    /** Un champ qui n'apparaît pas sur l'image ne la refait pas. */
    public function test_an_invisible_field_leaves_the_image_alone(): void
    {
        $service = app(SharePreviewService::class);

        $avant = $service->chemin($this->profile);

        $this->profile->forceFill(['phone' => '+221770000000'])->save();

        $this->assertSame($avant, $service->chemin($this->profile->refresh()));
    }

    /** Le nettoyage emporte tous les aperçus du profil, sans lister de dossier commun. */
    public function test_forgetting_clears_every_preview(): void
    {
        $service = app(SharePreviewService::class);
        $disque = Storage::disk('public');

        $service->png($this->profile);
        $disque->put('apercus/awa-ndiaye/anciennne.png', 'vieux');

        $service->forget($this->profile);

        $disque->assertMissing('apercus/awa-ndiaye/anciennne.png');
        $disque->assertMissing($service->chemin($this->profile));
    }

    // =======================================================================
    // LES BALISES
    // =======================================================================

    /**
     * LA PAGE PUBLIQUE DÉCLARE L'IMAGE, EN ADRESSE ABSOLUE.
     *
     * Les robots des messageries ne résolvent aucun chemin relatif : une URL
     * relative donne exactement le même résultat qu'une balise absente, sans
     * que rien ne le signale.
     */
    public function test_the_public_page_declares_an_absolute_image(): void
    {
        $html = $this->get(route('profile.public', 'awa-ndiaye'))->assertOk()->getContent();

        preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $trouve);

        $this->assertNotEmpty($trouve, 'La page publique ne déclare aucune image de partage.');

        $this->assertStringStartsWith(
            'http',
            $trouve[1],
            'L\'adresse de l\'image est relative : les messageries l\'ignoreront.'
        );
    }

    /**
     * LES DIMENSIONS SONT DÉCLARÉES.
     *
     * Sans elles, WhatsApp doit télécharger l'image avant de savoir quelle
     * place lui réserver — et affiche souvent le lien sans vignette en
     * attendant, c'est-à-dire au moment précis où le destinataire regarde.
     */
    public function test_the_image_dimensions_are_declared(): void
    {
        $html = $this->get(route('profile.public', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
        $this->assertStringContainsString('<meta property="og:image:height" content="630">', $html);
    }

    /** La vignette est GRANDE, pas un timbre-poste. */
    public function test_the_card_is_declared_as_a_large_image(): void
    {
        $html = $this->get(route('profile.public', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
    }

    /**
     * UNE PANNE DE GÉNÉRATION NE CASSE PAS LA PAGE PUBLIQUE.
     *
     * C'est la page qui prend tout le trafic du produit : un défaut de GD, un
     * disque plein ou une photo illisible doivent coûter une vignette, jamais
     * la carte elle-même.
     */
    public function test_a_failure_costs_the_thumbnail_never_the_page(): void
    {
        $this->mock(SharePreviewService::class, function ($mock) {
            $mock->shouldReceive('png')->andThrow(new \RuntimeException('disque plein'));
        });

        $this->get(route('profile.public', 'awa-ndiaye'))
            ->assertOk()
            ->assertSee('Awa Ndiaye', false);
    }

    /** Un profil non visible ne produit aucun aperçu : il n'y a rien à partager. */
    public function test_an_invisible_profile_has_no_preview(): void
    {
        $this->profile->forceFill(['is_active' => false])->save();

        $this->get(route('profile.public', 'awa-ndiaye'))->assertNotFound();
    }
}
