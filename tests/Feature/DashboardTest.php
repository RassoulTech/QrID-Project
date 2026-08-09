<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * TABLEAU DE BORD — densité, données réelles, états vides.
 *
 * La règle que ce fichier défend : aucun bloc factice, aucun zéro affiché.
 * Quand une donnée manque, l'écran dit quoi faire pour qu'elle apparaisse.
 */
class DashboardTest extends TestCase
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
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => Plan::where('slug', 'mensuel')->value('id'),
            'starts_at' => now(),
            'ends_at' => now()->addDays(20),
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    private function html(array $query = []): string
    {
        return $this->actingAs($this->user)
            ->get(route('dashboard', $query))
            ->assertOk()
            ->getContent();
    }

    // =======================================================================
    // ÉTATS VIDES — jamais un zéro
    // =======================================================================

    /**
     * CHAQUE CARTE AFFICHE SON CHIFFRE, ZÉRO COMPRIS.
     *
     * RÈGLE INVERSÉE, et c'est assumé. La version précédente masquait le
     * nombre quand il valait zéro, pour ne pas décourager. À l'usage, une
     * carte sans chiffre paraît cassée : le lecteur ne sait plus s'il n'a
     * aucune vue ou si la mesure ne fonctionne pas. Un zéro EST une
     * information — il dit que le compteur marche et qu'il attend.
     *
     * L'aide contextuelle reste, en dessous, tant que la valeur est nulle.
     */
    public function test_every_stat_card_shows_its_figure_even_at_zero(): void
    {
        $html = $this->html();

        // Les trois compteurs d'événements sont à zéro et l'affichent.
        $this->assertSame(
            3,
            substr_count($html, 'stat-tuile__n">0<'),
            'Un compteur à zéro n\'affiche pas son chiffre : la carte paraît cassée.'
        );

        // Et l'aide contextuelle accompagne le zéro, sans le remplacer.
        $this->assertStringContainsString('Partagez votre lien', $html);
        $this->assertStringContainsString('Aucune vue pour l\'instant', $html);
        $this->assertStringContainsString('Personne n\'a encore ouvert votre carte', $html);
    }

    public function test_real_figures_replace_the_guidance_once_data_exists(): void
    {
        ProfileEvent::factory()->count(4)->create([
            'profile_id' => $this->profile->id,
            'type' => ProfileEvent::TYPE_VIEW,
            'created_at' => now()->subDay(),
        ]);

        ProfileEvent::factory()->count(2)->create([
            'profile_id' => $this->profile->id,
            'type' => ProfileEvent::TYPE_SCAN,
        ]);

        $html = $this->html();

        $this->assertStringContainsString('>4<', $html);
        $this->assertStringContainsString('>2<', $html);
        $this->assertStringNotContainsString('Aucune vue pour l\'instant', $html);

        // L'histogramme apparaît, avec son équivalent textuel.
        $this->assertStringContainsString('class="chart"', $html);
        $this->assertStringContainsString('<caption>Vues par jour</caption>', $html);
    }

    /** Le sélecteur de période vit dans l'URL : partageable, rechargeable. */
    public function test_the_period_selector_lives_in_the_url(): void
    {
        ProfileEvent::factory()->count(3)->create([
            'profile_id' => $this->profile->id,
            'type' => ProfileEvent::TYPE_VIEW,
        ]);

        $this->assertStringContainsString(
            'href="'.route('dashboard', ['periode' => 30]).'"',
            $this->html()
        );

        // Une valeur fantaisiste retombe sur la période par défaut.
        $this->actingAs($this->user)->get(route('dashboard', ['periode' => 999]))->assertOk();
    }

    // =======================================================================
    // REQUÊTES SQL
    // =======================================================================

    /**
     * Le nombre total de requêtes de la page est CONSTANT.
     *
     * On mesure sur un jeu réduit puis sur un jeu volumineux : c'est l'écart
     * entre les deux qui révèle une boucle, pas la valeur absolue.
     */
    public function test_the_page_costs_a_constant_number_of_queries(): void
    {
        $mesurer = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $this->actingAs($this->user)->get(route('dashboard'))->assertOk();

            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        ProfileEvent::factory()->count(5)->create(['profile_id' => $this->profile->id]);

        /*
         | On JETTE la première mesure. Le garde d'authentification relit le
         | compte en base à la toute première requête d'un test, puis le garde
         | en mémoire : sans ce tour de chauffe, on comparerait une page à
         | froid à une page à chaud et l'écart d'une requête n'apprendrait rien.
         */
        $mesurer();
        $petit = $mesurer();

        ProfileEvent::factory()->count(300)->create(['profile_id' => $this->profile->id]);
        $grand = $mesurer();

        $this->assertSame(
            $petit,
            $grand,
            "Le coût de la page grandit avec le volume : {$petit} puis {$grand} requêtes."
        );

        // Garde-fou de niveau : au-delà, c'est qu'un bloc interroge en boucle.
        $this->assertLessThanOrEqual(20, $petit, "Page trop bavarde : {$petit} requêtes.");
    }

    // =======================================================================
    // PAGES DU MENU — aucune entrée ne mène au vide
    // =======================================================================

    /**
     * Les cinq entrées du menu aboutissent.
     *
     * « Mon QR Code » et « Statistiques » étaient des <span> inertes : deux
     * entrées sur cinq ne répondaient pas, ce qui laisse croire à une panne.
     */
    public function test_every_menu_entry_leads_somewhere(): void
    {
        foreach (['dashboard', 'profil.index', 'carte.qr', 'statistiques', 'compte.edit'] as $route) {
            $this->actingAs($this->user)->get(route($route))->assertOk();
        }
    }

    /** Le menu ne contient plus aucune entrée inerte. */
    public function test_the_menu_has_no_dead_entry(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('aria-disabled="true"', $html);
        $this->assertStringNotContainsString('href="#"', $html);

        foreach (['profil.index', 'carte.qr', 'statistiques'] as $route) {
            $this->assertStringContainsString(route($route), $html);
        }
    }

    /** Sans carte, ces pages orientent vers la création plutôt que d'échouer. */
    public function test_without_a_card_the_menu_pages_send_to_the_wizard(): void
    {
        $sansCarte = User::factory()->create(['email_verified_at' => now()]);

        foreach (['profil.index', 'carte.qr', 'statistiques'] as $route) {
            $this->actingAs($sansCarte)->get(route($route))
                ->assertRedirect(route('profile.create.step1'));
        }
    }

    public function test_the_statistics_page_shows_an_empty_state_rather_than_a_blank_chart(): void
    {
        $this->actingAs($this->user)->get(route('statistiques'))
            ->assertOk()
            ->assertSee('Aucun événement sur cette période')
            ->assertSee('Partagez votre QR Code', false);
    }

    // =======================================================================
    // THÈME
    // =======================================================================

    public function test_the_theme_is_applied_by_the_server_on_the_html_tag(): void
    {
        $this->assertStringNotContainsString('class="theme-dark"', $this->html());

        $this->actingAs($this->user)
            ->post(route('preferences.theme'), ['theme' => 'dark'])
            ->assertRedirect();

        $this->assertSame('dark', $this->user->fresh()->theme);
        $this->assertStringContainsString('theme-dark', $this->html());
    }

    /** La préférence suit la personne, pas le navigateur : elle est en base. */
    public function test_the_theme_preference_is_stored_on_the_account(): void
    {
        $this->actingAs($this->user)->post(route('preferences.theme'), ['theme' => 'dark']);

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'theme' => 'dark']);
    }

    public function test_an_unknown_theme_is_refused(): void
    {
        $this->actingAs($this->user)
            ->post(route('preferences.theme'), ['theme' => 'fluo'])
            ->assertSessionHasErrors('theme');

        $this->assertSame('light', $this->user->fresh()->theme);
    }

    // =======================================================================
    // NOTIFICATIONS
    // =======================================================================

    public function test_notifications_come_from_real_events_and_never_repeat(): void
    {
        $service = app(NotificationService::class);

        $service->premiereVue($this->user, $this->profile);
        $service->premiereVue($this->user, $this->profile);   // rejouée

        $this->assertSame(1, $this->user->alerts()->count(), 'La clé d\'unicité n\'a pas joué.');
    }

    public function test_the_bell_counts_unread_and_the_page_lists_them(): void
    {
        app(NotificationService::class)->premiereVue($this->user, $this->profile);

        $this->assertStringContainsString('app-bell__pastille', $this->html());

        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Votre carte a été consultée');
    }

    public function test_opening_a_notification_marks_it_read_and_leads_somewhere(): void
    {
        app(NotificationService::class)->premiereVue($this->user, $this->profile);
        $alerte = $this->user->alerts()->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('notifications.open', $alerte))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($alerte->fresh()->read_at);
    }

    public function test_a_notification_of_another_account_is_forbidden(): void
    {
        app(NotificationService::class)->premiereVue($this->user, $this->profile);
        $alerte = $this->user->alerts()->firstOrFail();

        $intrus = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($intrus)->get(route('notifications.open', $alerte))->assertForbidden();
    }

    public function test_no_notification_shows_an_explicit_empty_state(): void
    {
        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Aucune notification');
    }

    // =======================================================================
    // RECHERCHE
    // =======================================================================

    public function test_the_search_is_a_get_form_and_works_without_javascript(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('action="'.route('recherche').'"', $html);
        $this->assertStringContainsString('method="GET"', $html);
    }

    public function test_the_search_finds_the_card_by_name(): void
    {
        $this->actingAs($this->user)
            ->get(route('recherche', ['q' => $this->profile->first_name]))
            ->assertOk()
            ->assertSee($this->profile->full_name);
    }

    public function test_a_search_never_reaches_another_account(): void
    {
        $autre = User::factory()->create(['email_verified_at' => now()]);
        Profile::factory()->create(['user_id' => $autre->id, 'first_name' => 'Moussa', 'slug' => 'moussa-diop']);

        $this->actingAs($this->user)
            ->get(route('recherche', ['q' => 'Moussa']))
            ->assertOk()
            ->assertDontSee('moussa-diop')
            ->assertSee('Aucun résultat');
    }

    public function test_a_too_short_term_asks_for_more(): void
    {
        $this->actingAs($this->user)
            ->get(route('recherche', ['q' => 'a']))
            ->assertOk()
            ->assertSee('au moins deux caractères');
    }
}
