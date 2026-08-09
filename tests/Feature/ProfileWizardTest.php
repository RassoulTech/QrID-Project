<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parcours de création du profil.
 *
 * Ce qui est vérifié ici tient en une phrase : rien ne se perd, rien ne se
 * saute, et le serveur décide de tout.
 */
class ProfileWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TemplateSeeder::class);
        $this->user = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // Séquence
    // -----------------------------------------------------------------------

    public function test_step_two_is_unreachable_before_step_one(): void
    {
        $this->actingAs($this->user)
            ->get(route('profile.create.step2'))
            ->assertRedirect(route('profile.create.step1'));
    }

    public function test_step_three_redirects_to_the_first_missing_step(): void
    {
        $this->actingAs($this->user)
            ->withSession(['profile_wizard' => ['completed' => [1]]])
            ->get(route('profile.create.step3'))
            ->assertRedirect(route('profile.create.step2'));
    }

    // -----------------------------------------------------------------------
    // Reprise — aucune saisie perdue
    // -----------------------------------------------------------------------

    public function test_a_returning_user_finds_their_answers(): void
    {
        $this->actingAs($this->user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante',
        ]);

        // Retour sur l'étape 1 : les valeurs sont repositionnées.
        $this->actingAs($this->user)
            ->get(route('profile.create.step1'))
            ->assertOk()
            ->assertSee('value="Awa"', false)
            ->assertSee('value="Consultante"', false);
    }

    public function test_validation_errors_keep_the_other_fields(): void
    {
        $this->actingAs($this->user)
            ->from(route('profile.create.step1'))
            ->post(route('profile.store.step1'), [
                'first_name' => 'Awa',
                'last_name' => '',            // manquant
                'job_title' => 'Consultante',
            ])
            ->assertRedirect(route('profile.create.step1'))
            ->assertSessionHasErrors('last_name')
            ->assertSessionHasInput('first_name', 'Awa');

        $this->assertDatabaseCount('profiles', 0);
    }

    // -----------------------------------------------------------------------
    // Règles métier
    // -----------------------------------------------------------------------

    public function test_phone_is_normalised_and_profile_is_written_once(): void
    {
        $this->completeStepsOneAndTwo(phone: '77 383 13 64', whatsapp: '+221 78 637 93 02');

        $this->actingAs($this->user)->post(route('profile.store.step3'), [
            'template_id' => Template::first()->id,
            'primary_color' => '#0B3B2E',
        ])->assertRedirect(route('profile.preview'));

        $profile = Profile::firstOrFail();

        $this->assertSame('+221773831364', $profile->phone);
        $this->assertSame('+221786379302', $profile->whatsapp);
        $this->assertSame('awa-ndiaye', $profile->slug);
        $this->assertFalse($profile->is_active, 'Un profil naît toujours inactif.');
    }

    public function test_slug_collision_gets_a_numeric_suffix(): void
    {
        Profile::factory()->create(['slug' => 'awa-ndiaye']);

        $this->completeStepsOneAndTwo();

        $this->actingAs($this->user)->post(route('profile.store.step3'), [
            'template_id' => Template::first()->id,
            'primary_color' => '#0B3B2E',
        ]);

        $this->assertSame('awa-ndiaye-2', $this->user->fresh()->profile->slug);
    }

    public function test_empty_social_rows_are_ignored_not_rejected(): void
    {
        $this->actingAs($this->user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa', 'last_name' => 'Ndiaye', 'job_title' => 'Consultante',
        ]);

        $this->actingAs($this->user)->post(route('profile.store.step2'), [
            'phone' => '770000000',
            'socials' => [
                ['platform' => 'linkedin', 'url' => 'linkedin.com/in/awa'],
                ['platform' => '', 'url' => ''],   // ligne ajoutée puis laissée vide
            ],
        ])->assertRedirect(route('profile.create.step3'))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)->post(route('profile.store.step3'), [
            'template_id' => Template::first()->id,
            'primary_color' => '#0B3B2E',
        ]);

        $links = Profile::firstOrFail()->socialLinks;

        $this->assertCount(1, $links);
        $this->assertSame('https://linkedin.com/in/awa', $links->first()->url);
    }

    public function test_a_colour_outside_the_palette_is_refused(): void
    {
        $this->completeStepsOneAndTwo();

        $this->actingAs($this->user)
            ->from(route('profile.create.step3'))
            ->post(route('profile.store.step3'), [
                'template_id' => Template::first()->id,
                'primary_color' => '#FF00FF',
            ])
            ->assertSessionHasErrors('primary_color');

        $this->assertDatabaseCount('profiles', 0);
    }

    // -----------------------------------------------------------------------
    // Profil déjà créé
    // -----------------------------------------------------------------------

    /**
     * Un profil existe déjà : l'étape 1 ne recrée rien, elle bascule sur la
     * modification (ProfileWizardController::stepOne), qui recharge le profil
     * en session avant de revenir à l'étape 1.
     */
    public function test_the_wizard_hands_over_to_editing_once_a_profile_exists(): void
    {
        Profile::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('profile.create.step1'))
            ->assertRedirect(route('profile.edit'));

        // Et la bascule aboutit bien : retour à l'étape 1, en édition.
        $this->actingAs($this->user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('profile.create.step1'));
    }

    public function test_editing_reopens_the_wizard_and_keeps_the_slug(): void
    {
        $profile = Profile::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'awa-ndiaye',
            'first_name' => 'Awa',
        ]);

        $this->actingAs($this->user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('profile.create.step1'));

        $this->actingAs($this->user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa', 'last_name' => 'Diop', 'job_title' => 'Consultante',
        ]);
        $this->actingAs($this->user)->post(route('profile.store.step2'), ['phone' => '770000000']);
        $this->actingAs($this->user)->post(route('profile.store.step3'), [
            'template_id' => Template::first()->id,
            'primary_color' => '#0B3B2E',
        ]);

        // Un seul profil, mis à jour, et le lien public reste valable.
        $this->assertDatabaseCount('profiles', 1);
        $this->assertSame('Diop', $profile->fresh()->last_name);
        $this->assertSame('awa-ndiaye', $profile->fresh()->slug);
    }

    public function test_a_guest_cannot_enter_the_wizard(): void
    {
        $this->get(route('profile.create.step1'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------------

    private function completeStepsOneAndTwo(string $phone = '770000000', ?string $whatsapp = null): void
    {
        $this->actingAs($this->user)->post(route('profile.store.step1'), [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante',
        ]);

        $this->actingAs($this->user)->post(route('profile.store.step2'), array_filter([
            'phone' => $phone,
            'whatsapp' => $whatsapp,
        ]));
    }
}
