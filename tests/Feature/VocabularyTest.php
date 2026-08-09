<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GARDE-FOU DE VOCABULAIRE — COMPTE ≠ PROFIL.
 *
 * COMPTE (users)   : identifiants d'accès. Créé en premier, via register.
 * PROFIL (profiles): carte professionnelle. Créé APRÈS connexion.
 *
 * Ce test échoue si le mot « profil » réapparaît dans le parcours de création
 * de compte. Il est la garantie que la confusion ne revient pas.
 */
class VocabularyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Formulations interdites dans le parcours de création de COMPTE.
     * On cible les tournures d'ACTION, pas le mot « profil » seul : la landing
     * a le droit de décrire le produit (« un profil professionnel moderne »).
     */
    private const FORBIDDEN_IN_ACCOUNT_FLOW = [
        'Créer mon profil',
        'Créer un profil',
        'Créer votre profil',
        'Créez votre profil gratuitement',
        'créer mon profil gratuitement',
    ];

    public function test_landing_page_never_asks_to_create_a_profile(): void
    {
        $this->seed(PlanSeeder::class);

        $html = $this->get('/')->assertOk()->getContent();

        foreach (self::FORBIDDEN_IN_ACCOUNT_FLOW as $phrase) {
            $this->assertStringNotContainsString(
                $phrase,
                $html,
                "La landing mène à l'INSCRIPTION : elle ne doit pas dire « {$phrase} »."
            );
        }

        // Elle doit en revanche proposer explicitement de créer un compte.
        $this->assertTrue(
            str_contains($html, 'Créer un compte') || str_contains($html, 'Commencer gratuitement'),
            'La landing doit proposer de créer un compte.'
        );
    }

    public function test_registration_form_uses_account_vocabulary(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        foreach (self::FORBIDDEN_IN_ACCOUNT_FLOW as $phrase) {
            $this->assertStringNotContainsString(
                $phrase,
                $html,
                "Le formulaire d'inscription crée un COMPTE, pas un profil : « {$phrase} » interdit."
            );
        }

        $this->assertStringContainsString('Créer un compte', $html);
    }

    /** Aucun champ professionnel ne doit figurer dans l'inscription. */
    public function test_registration_form_has_no_professional_fields(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        foreach (['job_title', 'company', 'first_name', 'last_name', 'photo', 'whatsapp'] as $field) {
            $this->assertStringNotContainsString(
                'name="'.$field.'"',
                $html,
                "Le champ « {$field} » appartient au PROFIL, pas au formulaire de compte."
            );
        }

        // Seuls les quatre champs du compte sont attendus.
        foreach (['name', 'email', 'phone', 'password'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $html);
        }
    }

    public function test_login_page_uses_account_vocabulary(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        foreach (self::FORBIDDEN_IN_ACCOUNT_FLOW as $phrase) {
            $this->assertStringNotContainsString($phrase, $html);
        }
    }

    /** Le mot « profil » apparaît pour la première fois sur le dashboard. */
    public function test_dashboard_is_where_profile_vocabulary_starts(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString(
            'Créer mon profil',
            $html,
            'Le dashboard est le seul endroit où commence le vocabulaire du profil.'
        );
    }

    /** Le parcours de création de profil ne parle jamais de compte. */
    public function test_profile_wizard_never_mentions_account_creation(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(TemplateSeeder::class);

        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get(route('profile.create.step1'))
            ->assertOk()
            ->getContent();

        foreach (['Créer un compte', 'inscription', 'S\'inscrire'] as $phrase) {
            $this->assertStringNotContainsString(
                $phrase,
                $html,
                "Le parcours de PROFIL ne doit pas parler de compte : « {$phrase} »."
            );
        }
    }

    /** Les e-mails d'inscription parlent de compte, jamais de profil. */
    public function test_registration_emails_use_account_vocabulary(): void
    {
        $html = view('emails.registration.confirm', [
            'name' => 'Awa',
            'verifyUrl' => 'https://exemple.test/x',
            'ttlMinutes' => 60,
        ])->render();

        foreach (self::FORBIDDEN_IN_ACCOUNT_FLOW as $phrase) {
            $this->assertStringNotContainsString($phrase, $html);
        }

        $this->assertStringContainsString('compte', mb_strtolower($html));
    }
}
