<?php

namespace Tests\Feature\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * CONTRAT DES SEPT ÉCRANS D'AUTHENTIFICATION.
 *
 * Ce que ce fichier garantit, écran par écran :
 *   · la vue existe et se rend (une vue manquante donne un 500) ;
 *   · aucune clé de traduction n'atteint l'écran sous sa forme brute
 *     (« auth.failed » s'est déjà affiché tel quel en production) ;
 *   · chaque formulaire porte @csrf ;
 *   · le vocabulaire tient : pas de « profil » sur ces pages, pas de
 *     « Laravel », aucune connexion Google / Apple / Facebook ;
 *   · aucune URL en dur, aucun href="#".
 *
 * Les redirections, elles, sont couvertes par AuthenticationMatrixTest et
 * RegistrationMatrixTest : ce fichier ne les redouble pas.
 */
class AuthScreensTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les sept écrans, avec ce qu'il faut en session ou en base pour les
     * atteindre. Chaque entrée rend un écran distinct.
     *
     * @return array<string, array{0:string}>
     */
    public static function ecrans(): array
    {
        return [
            'connexion' => ['login'],
            'inscription' => ['register'],
            'confirmation e-mail' => ['registration.pending'],
            'mot de passe oublié' => ['password.request'],
            'réinitialisation' => ['password.reset'],
            'lien expiré' => ['registration.expired'],
            'erreur de saisie' => ['erreur'],
        ];
    }

    /** Ouvre l'écran demandé et renvoie son HTML. */
    private function html(string $ecran): string
    {
        return match ($ecran) {
            // État d'erreur : on provoque un vrai échec de validation puis on
            // suit la redirection, pour lire le formulaire tel que
            // l'utilisateur le retrouve.
            'erreur' => $this->followingRedirects()
                ->from(route('login'))
                ->post(route('login.store'), ['email' => 'pas-une-adresse', 'password' => ''])
                ->assertOk()
                ->getContent(),

            'registration.pending' => $this->withSession([
                'registration.pending_email' => 'awa@exemple.sn',
            ])->get(route('registration.pending'))->assertOk()->getContent(),

            'password.reset' => $this->get(route('password.reset', ['token' => Str::random(64)]))
                ->assertOk()->getContent(),

            default => $this->get(route($ecran))->assertOk()->getContent(),
        };
    }

    // =======================================================================
    // RENDU
    // =======================================================================

    #[DataProvider('ecrans')]
    public function test_the_screen_renders(string $ecran): void
    {
        $this->assertNotSame('', trim($this->html($ecran)));
    }

    /**
     * Aucune clé de traduction brute à l'écran.
     *
     * On cherche le motif « fichier.cle » pour les quatre fichiers de langue du
     * projet. C'est exactement ce qui s'affichait quand lang/fr/auth.php
     * manquait : l'utilisateur lisait « auth.failed ».
     */
    #[DataProvider('ecrans')]
    public function test_no_raw_translation_key_reaches_the_screen(string $ecran): void
    {
        $html = $this->html($ecran);

        $this->assertDoesNotMatchRegularExpression(
            '/\b(auth|passwords|validation|pagination)\.[a-z_]+(\.[a-z_]+)*\b/',
            strip_tags($html),
            "L'écran « {$ecran} » laisse passer une clé de traduction non résolue."
        );
    }

    // =======================================================================
    // VOCABULAIRE
    // =======================================================================

    /**
     * Le COMPTE, ce sont les identifiants. Le PROFIL, c'est la carte
     * professionnelle. Ces sept écrans ne parlent que du compte : le mot
     * « profil » n'y a rien à faire.
     */
    #[DataProvider('ecrans')]
    public function test_the_word_profile_never_appears(string $ecran): void
    {
        $texte = mb_strtolower(strip_tags($this->html($ecran)));

        $this->assertStringNotContainsString(
            'profil',
            $texte,
            "L'écran « {$ecran} » parle de profil ; ces pages ne traitent que du compte."
        );
    }

    /** Le produit ne cite jamais l'outil avec lequel il est construit. */
    #[DataProvider('ecrans')]
    public function test_the_framework_is_never_named(string $ecran): void
    {
        $this->assertStringNotContainsStringIgnoringCase(
            'laravel',
            $this->html($ecran),
            "L'écran « {$ecran} » nomme le framework."
        );
    }

    /**
     * Aucune connexion par un tiers : ni Google, ni Apple, ni Facebook.
     *
     * LES BALISES <link> SONT RETIRÉES AVANT LA RECHERCHE, et c'est une
     * précision, pas un assouplissement. La déclaration d'icône iOS s'écrit
     * `rel="apple-touch-icon"` — un nom imposé par le système, qu'aucune
     * alternative ne remplace. Sans ce retrait, le test déclarait une
     * « connexion Apple » sur les sept écrans à cause d'un favori.
     *
     * Ce qu'il continue de surveiller reste entier : boutons, liens,
     * formulaires, scripts et texte visible. Une balise <link> ne peut pas
     * être un bouton de connexion ; tout le reste, si.
     */
    #[DataProvider('ecrans')]
    public function test_no_third_party_sign_in(string $ecran): void
    {
        $html = mb_strtolower(preg_replace('/<link\b[^>]*>/i', '', $this->html($ecran)));

        foreach (['google', 'apple', 'facebook'] as $tiers) {
            $this->assertStringNotContainsString(
                $tiers,
                $html,
                "L'écran « {$ecran} » propose une connexion {$tiers}."
            );
        }
    }

    // =======================================================================
    // LIENS ET FORMULAIRES
    // =======================================================================

    /** Jamais de href="#" : un lien mort est une impasse. */
    #[DataProvider('ecrans')]
    public function test_no_dead_links(string $ecran): void
    {
        $this->assertStringNotContainsString(
            'href="#"',
            $this->html($ecran),
            "L'écran « {$ecran} » contient un lien mort."
        );
    }

    /**
     * Chaque formulaire porte son jeton CSRF.
     *
     * Les formulaires en GET en sont dispensés : ils n'écrivent rien (la page
     * « lien expiré » relance l'inscription par un GET vers /register).
     */
    #[DataProvider('ecrans')]
    public function test_every_writing_form_carries_a_csrf_token(string $ecran): void
    {
        $html = $this->html($ecran);

        preg_match_all('/<form\b[^>]*>.*?<\/form>/is', $html, $formulaires);

        $sansJeton = [];

        foreach ($formulaires[0] as $formulaire) {
            $enEcriture = preg_match('/method\s*=\s*"post"/i', $formulaire);

            if ($enEcriture && ! str_contains($formulaire, 'name="_token"')) {
                $sansJeton[] = mb_substr($formulaire, 0, 90);
            }
        }

        $this->assertSame([], $sansJeton, "Formulaire sans @csrf sur « {$ecran} » :\n".implode("\n", $sansJeton));
    }

    /**
     * Un champ mot de passe VIDE ne doit jamais ressembler à un champ rempli.
     *
     * Le marque-place « •••••••• » rendait les deux états indiscernables :
     * l'utilisateur envoyait un champ vide, lisait « Le champ mot de passe est
     * obligatoire », et voyait pourtant huit points dans le champ. Le piège
     * était maximal au retour d'une erreur, le mot de passe n'étant jamais
     * repositionné.
     */
    #[DataProvider('ecrans')]
    public function test_a_password_field_never_fakes_a_filled_value(string $ecran): void
    {
        $html = $this->html($ecran);

        preg_match_all('/<input\b[^>]*type="password"[^>]*>/i', $html, $champs);

        foreach ($champs[0] as $champ) {
            $this->assertStringNotContainsString(
                '•',
                $champ,
                "Un champ mot de passe de « {$ecran} » affiche de fausses puces."
            );
        }
    }

    /**
     * CHAQUE ÉCRAN A SON PROPRE VISUEL.
     *
     * Le gabarit portait une composition unique, écrite en dur, servie à
     * l'identique sur toutes les pages : même carte, mêmes pastilles, même fond
     * vert. Les maquettes montrent au contraire un visuel différent par écran.
     * Ce test compare les colonnes de droite deux à deux.
     */
    public function test_each_screen_carries_its_own_visual(): void
    {
        $visuels = [];

        foreach (array_keys(self::ecrans()) as $nom) {
            $html = $this->html(self::ecrans()[$nom][0]);

            preg_match('#<aside class="auth__aside.*?</aside>#s', $html, $m);

            $this->assertNotEmpty($m, "L'écran « {$nom} » n'a pas de colonne droite.");

            $visuels[$nom] = $m[0];
        }

        // « erreur de saisie » EST la page de connexion, en état d'erreur :
        // son visuel doit logiquement être le même. On l'écarte de la comparaison.
        unset($visuels['erreur de saisie']);

        $doublons = array_keys(array_diff_key($visuels, array_unique($visuels)));

        $this->assertSame(
            [],
            $doublons,
            "Ces écrans partagent le visuel d'un autre : ".implode(', ', $doublons)
        );
    }

    /** Le ton de la colonne droite suit la maquette, page par page. */
    public function test_the_aside_tone_follows_each_mockup(): void
    {
        $tons = [
            'login' => 'dark',
            'register' => 'light',
            'password.request' => 'light',
            'password.reset' => 'light',
            'registration.pending' => 'light',
            'registration.expired' => 'light',
        ];

        foreach ($tons as $route => $ton) {
            $this->assertStringContainsString(
                'auth__aside auth__aside--'.$ton,
                $this->html($route),
                "L'écran « {$route} » devrait porter le ton « {$ton} »."
            );
        }
    }

    /**
     * Un échec de connexion doit OUVRIR une porte, pas seulement en fermer une.
     *
     * L'écran ne proposait que « Créer un compte » : l'utilisateur bloqué
     * recréait donc un compte qu'il possédait déjà, plusieurs fois de suite.
     * La réinitialisation doit être visible à l'endroit et au moment où elle
     * sert. Elle s'affiche à chaque échec, donc ne révèle rien.
     */
    public function test_a_failed_login_offers_the_password_reset(): void
    {
        User::factory()->create(['email' => 'awa@exemple.sn', 'email_verified_at' => now()]);

        $html = $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login.store'), ['email' => 'awa@exemple.sn', 'password' => 'faux'])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('password.request'), $html);
        $this->assertStringContainsString('Inutile de recréer un compte', $html);
    }

    /**
     * Le message d'échec ne doit affirmer l'absence d'aucun compte : il
     * s'affiche aussi lorsque le compte existe et que seul le mot de passe
     * est erroné.
     */
    public function test_the_failure_message_never_claims_the_account_is_missing(): void
    {
        foreach (['inconnu@exemple.sn', 'awa@exemple.sn'] as $adresse) {
            User::factory()->create(['email' => 'awa@exemple.sn', 'email_verified_at' => now()]);

            $this->from(route('login'))
                ->post(route('login.store'), ['email' => $adresse, 'password' => 'faux']);

            $this->assertStringNotContainsString(
                'aucun compte',
                (string) session('errors')?->first('email'),
                'Le message affirme une absence de compte qu\'il ne peut pas garantir.'
            );

            User::query()->delete();
        }
    }

    /** Le sélecteur d'onglets est fait de LIENS vers deux routes distinctes. */
    public function test_the_tab_selector_is_two_real_links(): void
    {
        $connexion = $this->get(route('login'))->assertOk();

        $connexion->assertSee('href="'.route('login').'"', false);
        $connexion->assertSee('href="'.route('register').'"', false);
        $connexion->assertSee('aria-current="page"', false);

        // Et l'inscription garde SON URL : ce n'est pas un basculement local.
        $this->get(route('register'))->assertOk()->assertSee('aria-current="page"', false);
    }

    // =======================================================================
    // ÉTAT D'ERREUR — sous le champ, jamais regroupé en haut
    // =======================================================================

    public function test_a_field_error_is_shown_under_the_field_itself(): void
    {
        $html = $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login.store'), ['email' => 'inconnu@exemple.sn', 'password' => 'faux'])
            ->assertOk()
            ->getContent();

        // Le champ porte l'état d'erreur…
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('is-invalid', $html);

        // …et le message le décrit, donc il est rattaché au champ.
        $this->assertMatchesRegularExpression('/aria-describedby="[^"]*-err"/', $html);
        $this->assertStringContainsString(trans('auth.failed'), $html);

        // Pas de récapitulatif d'erreurs en tête de formulaire.
        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function test_a_password_is_never_written_back_into_the_page(): void
    {
        $html = $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'pas-une-adresse',
                'password' => 'mon-secret-a-moi',
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('mon-secret-a-moi', $html);

        // L'adresse, elle, est bien reposée : on ne fait pas resaisir.
        $this->assertStringContainsString('pas-une-adresse', $html);
    }

    // =======================================================================
    // LES PARCOURS QUE LES ÉCRANS SERVENT
    // =======================================================================

    public function test_a_successful_login_reaches_the_dashboard(): void
    {
        User::factory()->create([
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_a_failed_login_returns_a_translated_message(): void
    {
        User::factory()->create(['email' => 'awa@exemple.sn', 'email_verified_at' => now()]);

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => 'awa@exemple.sn', 'password' => 'faux'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_a_blocked_account_is_refused_with_a_clear_message(): void
    {
        User::factory()->create([
            'email' => 'suspendu@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
            'is_blocked' => true,
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'suspendu@exemple.sn',
                'password' => 'motdepasse-solide-123',
            ])
            ->assertSessionHasErrors(['email' => trans('auth.blocked')]);

        $this->assertGuest();
    }

    /** Les deux mots de passe doivent concorder : la maquette pose le champ. */
    public function test_registration_refuses_a_mismatched_confirmation(): void
    {
        $idem = 'jeton-idem';

        $this->withSession(['registration.idem' => $idem])
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Awa Ndiaye',
                'email' => 'awa@exemple.sn',
                'phone' => '77 383 13 64',
                'password' => 'motdepasse-solide-123',
                'password_confirmation' => 'pas-le-meme-123456',
                '_idem' => $idem,
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('pending_registrations', 0);
    }

    /** La page de confirmation annonce la limite de renvois. */
    public function test_the_confirmation_screen_states_the_resend_limit(): void
    {
        $html = $this->withSession([
            'registration.pending_email' => 'awa@exemple.sn',
            'registration.resend_count' => 2,
        ])->get(route('registration.pending'))->assertOk()->getContent();

        $this->assertStringContainsString('Il vous reste 1 renvoi', $html);
    }

    /** Limite atteinte : le bouton est désactivé, la page reste utile. */
    public function test_the_resend_button_is_disabled_once_the_limit_is_reached(): void
    {
        $html = $this->withSession([
            'registration.pending_email' => 'awa@exemple.sn',
            'registration.resend_count' => 3,
        ])->get(route('registration.pending'))->assertOk()->getContent();

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('Limite de renvois atteinte', $html);
    }

    /**
     * L'écran de confirmation ne révèle RIEN de l'état de l'adresse : le
     * compteur de renvois vient de la session, jamais de la base.
     */
    public function test_the_confirmation_screen_never_leaks_the_state_of_the_address(): void
    {
        // Une demande déjà renvoyée deux fois existe en base…
        PendingRegistration::create([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '+221773831364',
            'password' => Hash::make('motdepasse-solide-123'),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => Carbon::now()->addHour(),
            'resend_count' => 2,
            'last_sent_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);

        // …et pourtant l'écran affiche le compteur d'une demande neuve.
        $this->withSession(['registration.pending_email' => 'awa@exemple.sn'])
            ->get(route('registration.pending'))
            ->assertOk()
            ->assertSee('Il vous reste 3 renvois');
    }
}
