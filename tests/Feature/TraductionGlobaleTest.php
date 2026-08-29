<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Langue;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * LE GARDE-FOU DE LA TRADUCTION.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE DÉFAUT QU'IL ATTRAPE NE CASSE RIEN, ET C'EST TOUT LE PROBLÈME
 * ═══════════════════════════════════════════════════════════════════════
 * Quand une clé manque, Laravel rend LA CLÉ ELLE-MÊME. L'écran affiche
 * « dashboard.carte.titre » à la place d'un titre. La page répond 200, aucune
 * exception n'est levée, aucun test existant ne tombe.
 *
 * C'est exactement le mode de panne qui a laissé `f__input` — une classe CSS
 * inexistante — dépouiller le formulaire de contact pendant des semaines :
 * « rien n'échouait, aucun test ne tombait ; une classe absente ne casse pas
 * une page, elle la laisse nue ».
 *
 * Ce test parcourt donc la table de routage RÉELLE, dans les deux langues, et
 * échoue si une clé brute apparaît dans le HTML servi. Ajouter une route
 * l'ajoute au test ; il n'y a rien à tenir à jour ici.
 */
class TraductionGlobaleTest extends TestCase
{
    use RefreshDatabase;

    /** Routes qu'on ne peut pas appeler à l'aveugle, avec la raison. */
    private const IGNOREES = [
        'registration.confirm',   // exige un jeton signé à usage unique
        'registration.abandon',   // idem
        'password.reset',         // exige un jeton de réinitialisation
    ];

    /**
     * LES PRÉFIXES QUI TRAHISSENT UNE CLÉ NON RÉSOLUE.
     *
     * On ne cherche pas « tout ce qui ressemble à une clé » : `app.name`,
     * `bootstrap.min.css` et cent autres chaînes y répondraient. On cherche
     * NOS groupes, ceux qui existent dans lang/, et eux seuls.
     */
    private const GROUPES = [
        'admin', 'auth', 'card', 'common', 'dashboard', 'emails', 'errors',
        'landing', 'legal', 'navigation', 'passwords', 'payment', 'profile',
        'subscription', 'validation',
    ];

    // =======================================================================
    // 1. AUCUNE CLÉ BRUTE, NULLE PART, DANS AUCUNE DES DEUX LANGUES
    // =======================================================================

    public function test_no_raw_translation_key_is_ever_displayed(): void
    {
        [$user, $remplacements] = $this->contexte();

        $echecs = [];

        foreach (Langue::disponibles() as $langue) {
            foreach ($this->routesGet() as $nom => $uri) {
                foreach ($remplacements as $cle => $valeur) {
                    $uri = str_replace(['{'.$cle.'}', '{'.$cle.'?}'], $valeur, $uri);
                }

                if (str_contains($uri, '{')) {
                    continue;   // paramètre inconnu : non testable à l'aveugle
                }

                $reponse = $this->actingAs($user)
                    ->withUnencryptedCookie(Langue::nomDuCookie(), $langue)
                    ->get('/'.ltrim($uri, '/'));

                if ($reponse->getStatusCode() >= 400) {
                    continue;   // c'est le rôle d'EveryRouteRendersTest
                }

                foreach ($this->clesBrutes($reponse->getContent()) as $brute) {
                    $echecs[] = "[{$langue}] {$nom} ({$uri}) → {$brute}";
                }
            }
        }

        $this->assertSame([], array_unique($echecs),
            "Clés de traduction non résolues :\n".implode("\n", array_unique($echecs)));
    }

    /** Les pages d'erreur portent leur propre gabarit : on les vérifie aussi. */
    public function test_error_pages_are_translated_in_both_languages(): void
    {
        foreach (Langue::disponibles() as $langue) {
            $reponse = $this->withUnencryptedCookie(Langue::nomDuCookie(), $langue)
                ->get('/cette-page-ne-peut-pas-exister-'.uniqid());

            $reponse->assertNotFound();

            $this->assertSame([], $this->clesBrutes($reponse->getContent()),
                "Page 404 en « {$langue} » : clé brute affichée.");
        }

        // Et le texte doit RÉELLEMENT changer d'une langue à l'autre.
        $fr = $this->withUnencryptedCookie(Langue::nomDuCookie(), Langue::FRANCAIS)
            ->get('/introuvable-'.uniqid())->getContent();

        $en = $this->withUnencryptedCookie(Langue::nomDuCookie(), Langue::ANGLAIS)
            ->get('/introuvable-'.uniqid())->getContent();

        $this->assertStringContainsString('Page introuvable', $fr);
        $this->assertStringContainsString('Page not found', $en);
    }

    // =======================================================================
    // 2. LA LANGUE TIENT SUR PLUSIEURS PAGES, ET À TRAVERS LA CONNEXION
    // =======================================================================

    /**
     * TROIS PAGES CONSÉCUTIVES, PUIS LA CONNEXION.
     *
     * C'est le parcours exact qui échouait : basculer sur la landing, puis
     * naviguer, puis se connecter. La langue devait retomber en français à
     * l'une des trois étapes.
     */
    public function test_the_chosen_language_survives_navigation_and_login(): void
    {
        $mot = 'motdepasse-solide';

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt($mot),
            'locale' => Langue::FRANCAIS,
        ]);

        // ── Le visiteur choisit l'anglais depuis la landing ────────────
        $this->from(route('home'))
            ->post(route('preferences.langue'), ['langue' => Langue::ANGLAIS])
            ->assertCookie(Langue::nomDuCookie(), Langue::ANGLAIS, encrypted: false);

        $this->assertSame(Langue::ANGLAIS, session(Langue::cleDeSession()),
            'Le choix doit être écrit EN SESSION, pas seulement dans un cookie : '
            .'c\'est lui qui sera reporté sur le compte à la connexion.');

        // ── Trois pages consécutives, sans repasser par le sélecteur ───
        foreach ([route('home'), route('login'), route('register')] as $page) {
            $this->get($page)->assertOk();

            $this->assertSame(Langue::ANGLAIS, app()->getLocale(),
                "La langue est retombée sur « ".app()->getLocale()." » en visitant {$page}.");
        }

        // ── La connexion ──────────────────────────────────────────────
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => $mot,
        ]);

        $this->assertSame(Langue::ANGLAIS, $user->fresh()->locale,
            'Le choix fait avant la connexion doit être reporté sur le compte.');

        $this->get(route('dashboard'))->assertOk();

        $this->assertSame(Langue::ANGLAIS, app()->getLocale());
    }

    /**
     * ELLE SURVIT AUSSI À LA DÉCONNEXION PUIS À LA RECONNEXION.
     *
     * La déconnexion vide la session. Sans la colonne en base, le choix
     * disparaîtrait avec elle.
     */
    /**
     * LE CHOIX ANONYME SURVIT À UNE CONNEXION, MÊME SESSION PERDUE.
     *
     * ═══════════════════════════════════════════════════════════════════
     * CE SCÉNARIO N'A PAS ÉTÉ IMAGINÉ : IL A ÉTÉ OBSERVÉ
     * ═══════════════════════════════════════════════════════════════════
     * Dans un navigateur, la langue choisie disparaissait au moment précis
     * de la connexion. La chaîne était celle-ci :
     *
     *   1. visiteur anonyme choisit l'anglais → session ET cookie ;
     *   2. une première connexion échoue      → la session est vidée ;
     *   3. il se connecte pour de bon         → session vide, cookie intact ;
     *   4. le report ne lisait que la SESSION → rien à reporter ;
     *   5. le compte reste en français, et la préférence du compte
     *      l'emporte ensuite sur le cookie à CHAQUE requête.
     *
     * Le choix était donc perdu définitivement, par la seule action de se
     * connecter — et c'est irrattrapable pour l'utilisateur, qui doit
     * rechoisir sa langue une fois connecté.
     *
     * Le cookie est le niveau prévu pour survivre à la session. Le report
     * le consulte désormais en secours.
     */
    public function test_an_anonymous_choice_survives_login_even_without_a_session(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'locale' => Langue::FRANCAIS,
        ]);

        // Le cookie porte le choix ; la session, elle, est vide — exactement
        // l'état laissé par une tentative de connexion refusée.
        // withUnencryptedCookie, et PAS withCookie : ce cookie figure dans la
        // liste des non chiffres (bootstrap/app.php). withCookie l'aurait
        // chiffre, EncryptCookies ne l'aurait pas dechiffre — puisqu'il est
        // justement exclu — et le test aurait lu une chaine illisible en
        // croyant tester le code.
        $this->withUnencryptedCookie(Langue::nomDuCookie(), Langue::ANGLAIS);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertSame(
            Langue::ANGLAIS,
            $user->fresh()->locale,
            'Le choix anonyme porté par le cookie doit être reporté sur le compte.'
        );
    }

    public function test_the_language_survives_a_logout_and_a_new_session(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'locale' => Langue::ANGLAIS,
        ]);

        $this->actingAs($user)->post(route('logout'));

        // Session neuve, aucun cookie : seule la base porte encore le choix.
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();

        $this->assertSame(Langue::ANGLAIS, app()->getLocale());
    }

    /**
     * LE COOKIE PREND LE RELAIS QUAND LA SESSION EXPIRE.
     *
     * Une session dure deux heures, et disparaît à chaque redémarrage du
     * conteneur avec un driver en mémoire. Sans ce troisième niveau,
     * quelqu'un qui revient le lendemain retrouve le français sans avoir
     * rien demandé.
     */
    public function test_the_cookie_takes_over_when_the_session_is_gone(): void
    {
        $this->flushSession();

        $this->withUnencryptedCookie(Langue::nomDuCookie(), Langue::ANGLAIS)
            ->get(route('home'))
            ->assertOk();

        $this->assertSame(Langue::ANGLAIS, app()->getLocale());
    }

    // =======================================================================
    // 3. LA NÉGOCIATION SUR ACCEPT-LANGUAGE
    // =======================================================================

    /**
     * UNE PREMIÈRE VISITE SUIT LE NAVIGATEUR.
     *
     * Un anglophone qui arrive par un lien partagé n'a ni compte, ni session,
     * ni cookie. La seule chose qu'on sache de lui est ce que son navigateur
     * annonce.
     */
    public function test_a_first_visit_follows_the_browser(): void
    {
        $this->withHeader('Accept-Language', 'en-GB,en;q=0.9,fr;q=0.8')
            ->get(route('home'))
            ->assertOk();

        $this->assertSame(Langue::ANGLAIS, app()->getLocale());
    }

    /** Une langue inconnue du produit retombe sur le français. */
    public function test_an_unknown_browser_language_falls_back_to_french(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
            ->get(route('home'))
            ->assertOk();

        $this->assertSame(Langue::FRANCAIS, app()->getLocale());
    }

    /** Un choix explicite l'emporte toujours sur le navigateur. */
    public function test_an_explicit_choice_beats_the_browser(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->withUnencryptedCookie(Langue::nomDuCookie(), Langue::FRANCAIS)
            ->get(route('home'))
            ->assertOk();

        $this->assertSame(Langue::FRANCAIS, app()->getLocale());
    }

    // =======================================================================
    // 4. LA PAGE PUBLIQUE SUIT LE VISITEUR, PAS LE PORTEUR
    // =======================================================================

    public function test_a_public_card_is_shown_in_the_visitors_language(): void
    {
        $porteur = User::factory()->create([
            'email_verified_at' => now(),
            'locale' => Langue::FRANCAIS,
        ]);

        $profil = Profile::factory()->create([
            'user_id' => $porteur->id,
            'is_active' => true,
            'job_title' => 'Architecte',
            'company' => 'Atelier Teranga',
        ]);

        // isPubliclyVisible() consulte l'abonnement du PORTEUR : sans lui, la
        // carte rend « carte inactive », pas la carte.
        Subscription::factory()->active()->create([
            'user_id' => $porteur->id,
            'plan_id' => Plan::factory()->create()->id,
        ]);

        $reponse = $this->withUnencryptedCookie(Langue::nomDuCookie(), Langue::ANGLAIS)
            ->get(route('profile.public', $profil->slug))
            ->assertOk();

        $this->assertSame(Langue::ANGLAIS, app()->getLocale(),
            'La carte publique doit suivre le VISITEUR, jamais le propriétaire du profil.');

        // Le CONTENU SAISI, lui, n'est jamais traduit.
        $reponse->assertSee('Architecte')->assertSee('Atelier Teranga');
    }

    // -----------------------------------------------------------------------

    /**
     * Les clés non résolues présentes dans une réponse HTML.
     *
     * ATTENTION AUX FAUX POSITIFS. Le HTML contient des noms de fichiers
     * (« app-Bx3k.css »), des attributs, du JavaScript. On exige donc :
     *
     *   · un groupe qui existe RÉELLEMENT dans lang/ ;
     *   · au moins deux segments derrière, comme toutes nos clés ;
     *   · aucune extension de fichier connue ;
     *   · rien qui suive un « / » — ce serait un chemin.
     *
     * @return list<string>
     */
    private function clesBrutes(?string $html): array
    {
        if ($html === null || $html === '') {
            return [];
        }

        // Le JavaScript embarqué et les feuilles de style ne sont pas du texte
        // lu : les y chercher ne produirait que du bruit.
        $html = (string) preg_replace('#<script\b.*?</script>#si', ' ', $html);
        $html = (string) preg_replace('#<style\b.*?</style>#si', ' ', $html);

        $groupes = implode('|', self::GROUPES);

        /*
         | UN SEUL SEGMENT SUFFIT — le motif exigeait deux.
         |
         | `admin.clients.titre` etait attrape, mais `auth.failed` passait
         | au travers : deux segments etaient exiges apres le groupe. Or
         | les cles les plus courtes sont justement celles des messages
         | d'erreur, ceux qu'on lit au pire moment.
         |
         | Le filtre d'extensions plus bas ecarte `common.css` et consorts,
         | qui sont la seule chose que ce relachement laisse passer.
         */

        preg_match_all(
            '/(?<![\w\/.-])((?:'.$groupes.')(?:\.[a-z0-9_]+){1,})(?![\w-])/',
            $html,
            $trouves
        );

        $brutes = [];

        foreach ($trouves[1] as $candidat) {
            // « common.css », « admin.js » : des fichiers, pas des clés.
            if (preg_match('/\.(css|js|php|json|map|svg|png|woff2?)$/', $candidat)) {
                continue;
            }

            $brutes[] = $candidat;
        }

        return array_values(array_unique($brutes));
    }

    /** @return array<string, string> Routes GET nommées, par nom → uri. */
    private function routesGet(): array
    {
        $uris = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $nom = $route->getName();

            if (! $nom || in_array($nom, self::IGNOREES, true)) {
                continue;
            }

            $uris[$nom] = $route->uri();
        }

        return $uris;
    }

    /** @return array{0:User, 1:array<string,string>} */
    private function contexte(): array
    {
        $this->seed(TemplateSeeder::class);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_ADMIN,
        ]);

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Subscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => Plan::factory()->create()->id,
        ]);

        return [$user, ['slug' => $profile->slug]];
    }
}
