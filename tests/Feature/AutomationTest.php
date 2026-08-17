<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * AUTOMATISATION — déclencheur de tâches et capture des prospects.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX SENS, DEUX RISQUES OPPOSÉS
 * ═══════════════════════════════════════════════════════════════════════
 * MAKE VERS NOUS : une adresse publique qui déclenche des envois d'e-mails
 * et un message Discord. Le risque est qu'un inconnu la trouve et la
 * martèle. Le jeton est donc la propriété centrale, et son absence doit
 * fermer la route entièrement.
 *
 * NOUS VERS MAKE : un appel qui se produit PENDANT la confirmation d'un
 * compte. Le risque est qu'une panne de Make coûte l'inscription. Un
 * prospect manqué coûte un contact ; une inscription refusée coûte le client.
 */
class AutomationTest extends TestCase
{
    use RefreshDatabase;

    private const JETON = 'jeton-de-test-suffisamment-long-pour-etre-serieux';

    protected function setUp(): void
    {
        parent::setUp();

        // Contexte fixé, jamais lu dans .env : un test qui dépend de la
        // configuration locale de celui qui le lance ne dit plus rien.
        config([
            'automation.schedule.token' => null,
            'automation.make.webhook' => null,
            'automation.make.secret' => null,
            'automation.whatsapp_groupe' => null,
        ]);
    }

    // =======================================================================
    // MAKE VERS NOUS — le déclencheur
    // =======================================================================

    /**
     * SANS JETON CONFIGURÉ, LA ROUTE N'EXISTE PAS.
     *
     * C'est le comportement voulu tant que rien n'est réglé : une route
     * ouverte qu'on croit fermée est pire qu'une route absente.
     */
    public function test_the_trigger_is_invisible_until_a_token_is_configured(): void
    {
        $this->post(route('automation.schedule'))->assertNotFound();
    }

    /** Un jeton faux rend 404, jamais 401 : on ne confirme pas l'adresse. */
    public function test_a_wrong_token_reveals_nothing(): void
    {
        config(['automation.schedule.token' => self::JETON]);

        $this->withHeaders(['X-Automation-Token' => 'faux'])
            ->post(route('automation.schedule'))
            ->assertNotFound();
    }

    /** Sans en-tête du tout, même réponse. */
    public function test_a_missing_token_is_refused(): void
    {
        config(['automation.schedule.token' => self::JETON]);

        $this->post(route('automation.schedule'))->assertNotFound();
    }

    /** Le bon jeton déclenche le planificateur et rend un compte rendu. */
    public function test_the_right_token_runs_the_scheduler(): void
    {
        config(['automation.schedule.token' => self::JETON]);

        $this->withHeaders(['X-Automation-Token' => self::JETON])
            ->post(route('automation.schedule'))
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'duree_ms', 'message']);
    }

    /**
     * L'APPEL FONCTIONNE SANS JETON CSRF.
     *
     * Make appelle de machine à machine : aucune session, aucun cookie,
     * aucun jeton à présenter. Laissée dans le groupe « web », la route
     * aurait rendu 419 à chaque appel — et le scénario aurait paru cassé
     * sans raison lisible.
     */
    public function test_the_call_works_without_a_csrf_token(): void
    {
        config(['automation.schedule.token' => self::JETON]);

        $this->withHeaders(['X-Automation-Token' => self::JETON])
            ->post(route('automation.schedule'))
            ->assertOk();
    }

    /** Le jeton est accepté en paramètre d'URL, pour les outils qui l'exigent. */
    public function test_the_token_is_also_accepted_in_the_query(): void
    {
        config(['automation.schedule.token' => self::JETON]);

        $this->get(route('automation.schedule', ['token' => self::JETON]))->assertOk();
    }

    /**
     * UNE MINUTE CREUSE EST UNE RÉPONSE NORMALE, et elle le dit.
     *
     * `schedule:run` ne lance que ce qui est dû. Sans cette phrase, chaque
     * minute sans tâche ressemblerait à une panne dans le journal de Make.
     */
    public function test_an_idle_minute_says_so_plainly(): void
    {
        config(['automation.schedule.token' => self::JETON]);

        $reponse = $this->withHeaders(['X-Automation-Token' => self::JETON])
            ->post(route('automation.schedule'))
            ->assertOk();

        $this->assertNotSame('', $reponse->json('message'));
    }

    // =======================================================================
    // NOUS VERS MAKE — la capture des prospects
    // =======================================================================

    private function planEssai(): void
    {
        Plan::factory()->create([
            'slug' => 'essai-gratuit', 'name' => 'Essai gratuit',
            'duration_days' => 15, 'price_fcfa' => 0, 'is_active' => true,
        ]);
    }

    /** Un compte confirmé est transmis à Make. */
    public function test_a_confirmed_account_is_sent_to_make(): void
    {
        Mail::fake();
        Http::fake();
        $this->planEssai();

        config(['automation.make.webhook' => 'https://hook.make.test/abc']);

        $user = User::factory()->create(['name' => 'Awa Ndiaye', 'email' => 'awa@exemple.sn']);

        event(new UserRegistered($user));

        Http::assertSentCount(1);

        /** @var Request $requete */
        $requete = Http::recorded()[0][0];
        $charge = json_decode($requete->body(), true);

        $this->assertSame('inscription', $charge['evenement']);
        $this->assertSame('awa@exemple.sn', $charge['donnees']['email']);
        $this->assertSame('Awa Ndiaye', $charge['donnees']['nom']);
    }

    /**
     * AUCUN SECRET D'AUTHENTIFICATION NE SORT.
     *
     * Le mot de passe, même haché, et l'identifiant Google n'ont rien à faire
     * chez un service d'automatisation : chacun serait une donnée de plus à
     * protéger chez un tiers, sans aucun usage.
     */
    public function test_no_authentication_secret_ever_leaves(): void
    {
        Mail::fake();
        Http::fake();
        $this->planEssai();

        config(['automation.make.webhook' => 'https://hook.make.test/abc']);

        $user = User::factory()->create([
            'email' => 'awa@exemple.sn',
            'google_id' => 'google-secret-123',
        ]);

        event(new UserRegistered($user));

        $corps = Http::recorded()[0][0]->body();

        $this->assertStringNotContainsString('google-secret-123', $corps);
        $this->assertStringNotContainsString('password', $corps);
        $this->assertStringNotContainsString('remember_token', $corps);
    }

    /**
     * LA SIGNATURE PORTE SUR LA CHAÎNE EXACTE TRANSMISE.
     *
     * Première version : le corps était signé puis ré-encodé pour l'envoi.
     * Les deux encodages diffèrent — accents échappés ou non — donc la
     * signature n'aurait JAMAIS correspondu. Le scénario Make aurait rejeté
     * chaque appel, et l'échec se serait manifesté par un silence complet.
     */
    public function test_the_signature_matches_the_body_actually_sent(): void
    {
        Mail::fake();
        Http::fake();
        $this->planEssai();

        config([
            'automation.make.webhook' => 'https://hook.make.test/abc',
            'automation.make.secret' => 'secret-partage',
        ]);

        // Un nom accentué : c'est précisément ce qui diverge entre deux
        // encodages JSON.
        $user = User::factory()->create(['name' => 'Aïssatou Bâ', 'email' => 'aissatou@exemple.sn']);

        event(new UserRegistered($user));

        $requete = Http::recorded()[0][0];

        $this->assertSame(
            hash_hmac('sha256', $requete->body(), 'secret-partage'),
            $requete->header('X-QrID-Signature')[0],
            'La signature ne correspond pas au corps envoyé : Make rejettera tous les appels.'
        );
    }

    /** Sans secret, aucune signature n'est envoyée — le scénario ne doit pas croire vérifier. */
    public function test_no_signature_without_a_shared_secret(): void
    {
        Mail::fake();
        Http::fake();
        $this->planEssai();

        config(['automation.make.webhook' => 'https://hook.make.test/abc']);

        event(new UserRegistered(User::factory()->create()));

        $this->assertEmpty(Http::recorded()[0][0]->header('X-QrID-Signature'));
    }

    /**
     * UNE PANNE DE MAKE NE COÛTE PAS L'INSCRIPTION.
     *
     * C'est le test le plus important de cette moitié. Le compte doit exister
     * et l'essai s'ouvrir, même si le service d'automatisation est mort.
     */
    public function test_a_dead_make_never_costs_the_registration(): void
    {
        Mail::fake();
        Http::fake(fn () => throw new \RuntimeException('Make injoignable'));
        $this->planEssai();

        config(['automation.make.webhook' => 'https://hook.make.test/abc']);

        $user = User::factory()->create();

        event(new UserRegistered($user));

        $this->assertNotNull(
            $user->refresh()->activeSubscription(),
            'L\'essai gratuit doit s\'ouvrir même si Make est injoignable.'
        );

        Mail::assertSent(WelcomeMail::class);
    }

    /** Sans webhook configuré, aucun appel n'est tenté. */
    public function test_nothing_is_sent_without_a_webhook(): void
    {
        Mail::fake();
        Http::fake();
        $this->planEssai();

        event(new UserRegistered(User::factory()->create()));

        Http::assertNothingSent();
    }

    // =======================================================================
    // LE GROUPE WHATSAPP
    // =======================================================================

    /** Le lien du groupe figure dans l'e-mail de bienvenue. */
    public function test_the_welcome_email_invites_to_the_client_group(): void
    {
        Mail::fake();
        $this->planEssai();

        config(['automation.whatsapp_groupe' => 'https://chat.whatsapp.com/ABC123']);

        event(new UserRegistered(User::factory()->create()));

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $m) {
            return str_contains($m->render(), 'https://chat.whatsapp.com/ABC123');
        });
    }

    /**
     * SANS LIEN CONFIGURÉ, LE PARAGRAPHE DISPARAÎT.
     *
     * Un client qui clique sur une invitation morte conclut que le groupe
     * n'existe pas — ou pire, qu'il n'y a pas été accepté.
     */
    public function test_no_dead_invitation_when_the_group_is_not_configured(): void
    {
        Mail::fake();
        $this->planEssai();

        event(new UserRegistered(User::factory()->create()));

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $m) {
            return ! str_contains($m->render(), 'chat.whatsapp.com')
                && ! str_contains($m->render(), 'Rejoindre le groupe');
        });
    }

    /**
     * L'ESPACE CLIENT PROPOSE AUSSI LE GROUPE.
     *
     * L'e-mail de bienvenue arrive une fois, se lit en diagonale, et se perd.
     * Le besoin d'aide, lui, arrive plus tard — souvent des semaines après.
     * Le doublon est donc voulu.
     */
    public function test_the_client_area_also_offers_the_group(): void
    {
        config(['automation.whatsapp_groupe' => 'https://chat.whatsapp.com/ABC123']);

        $this->actingAs($this->clientAvecCarte())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('https://chat.whatsapp.com/ABC123', false)
            ->assertSee('Rejoindre le groupe', false);
    }

    /** Sans lien configuré, la carte disparaît de l'espace client aussi. */
    public function test_the_client_area_shows_nothing_without_a_group(): void
    {
        $this->actingAs($this->clientAvecCarte())->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Rejoindre le groupe', false);
    }

    /**
     * Un compte AVEC carte — sans quoi le tableau de bord rend son état vide,
     * qui n'a pas de colonne latérale.
     *
     * Ce n'est pas un contournement : quelqu'un qui n'a pas encore créé sa
     * carte vient de recevoir l'e-mail de bienvenue, qui porte déjà
     * l'invitation. Le rappel de l'espace client s'adresse à celui qui
     * l'a oubliée, des semaines plus tard.
     */
    private function clientAvecCarte(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Profile::factory()->for($user)->create();

        return $user;
    }

    /**
     * LE LIEN DU GROUPE N'APPARAÎT JAMAIS SUR UNE PAGE PUBLIQUE.
     *
     * Il donne accès à un espace réservé aux clients : quiconque l'obtient
     * peut y entrer. Le publier sur la page d'accueil reviendrait à ouvrir le
     * groupe à tout Internet.
     */
    public function test_the_group_link_never_appears_on_a_public_page(): void
    {
        config(['automation.whatsapp_groupe' => 'https://chat.whatsapp.com/ABC123']);

        foreach ([route('home'), route('login'), route('register')] as $adresse) {
            $this->assertStringNotContainsString(
                'chat.whatsapp.com/ABC123',
                $this->get($adresse)->getContent(),
                "Le lien du groupe client fuit sur {$adresse}"
            );
        }
    }
}
