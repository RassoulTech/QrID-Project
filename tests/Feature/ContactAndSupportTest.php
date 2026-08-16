<?php

namespace Tests\Feature;

use App\Mail\ContactMail;
use App\Models\ContactMessage;
use App\Models\User;
use App\Support\AideContextuelle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * FORMULAIRE DE CONTACT ET BOUTON WHATSAPP.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS PROTÈGENT
 * ═══════════════════════════════════════════════════════════════════════
 * Un message perdu.
 *
 * Un formulaire de contact qui se contente d'envoyer un e-mail perd tout dès
 * que l'envoi échoue — et l'envoi a échoué trois jours durant sur ce projet,
 * en production, sans que rien ne le signale. Un message perdu ici, ce n'est
 * pas un désagrément : c'est un client qui a pris la peine d'écrire, qui
 * n'aura aucune réponse, et qui en conclura que personne ne répond.
 *
 * L'ordre « écrire d'abord, notifier ensuite » est donc la propriété centrale
 * de ce fichier.
 */
class ContactAndSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         | LE CONTEXTE EST FIXÉ ICI, PAS LU DANS .env — et c'est un correctif.
         |
         | Ces tests lisaient SUPPORT_EMAIL depuis le fichier .env du poste de
         | développement. Le jour où il a été renseigné, un test qui vérifiait
         | l'envoi vers les comptes administrateurs s'est mis à échouer — sans
         | qu'une seule ligne de code applicatif ait changé.
         |
         | Un test qui dépend de la configuration locale de celui qui le lance
         | n'est pas un test : il ne dit plus si le produit fonctionne, il dit
         | à quoi ressemble un fichier qui n'est même pas versionné.
         |
         | Chaque test qui s'intéresse à l'adresse de support la déclare donc
         | explicitement ; les autres partent d'une valeur vide, qui exerce le
         | repli sur les comptes en base.
         */
        config(['landing.support.email' => null]);
    }

    /** @return array<string, string> */
    private function messageValide(array $remplace = []): array
    {
        return array_merge([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '77 383 13 64',
            'subject' => 'commande',
            'message' => 'Bonjour, je souhaite commander cinquante cartes imprimées pour mon cabinet.',
        ], $remplace);
    }

    /** Rend tout envoi impossible, comme un service de messagerie en panne. */
    private function messagerieEnPanne(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP injoignable'));
    }

    // =======================================================================
    // LE CAS NOMINAL
    // =======================================================================

    public function test_a_message_is_stored_and_the_team_is_notified(): void
    {
        Mail::fake();

        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'equipe@qrid.sn']);

        $this->post(route('contact.store'), $this->messageValide())
            ->assertRedirect(route('home').'#contact')
            ->assertSessionHas('contact.envoye');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'awa@exemple.sn',
            'subject' => 'commande',
        ]);

        Mail::assertSent(ContactMail::class, fn (ContactMail $m) => $m->hasTo('equipe@qrid.sn'));
    }

    /**
     * LE MESSAGE SURVIT À UNE MESSAGERIE MORTE.
     *
     * C'est le test le plus important du fichier. La base est la source de
     * vérité ; l'e-mail n'est qu'une alerte. Si l'alerte se perd, le message
     * reste — et l'expéditeur reçoit la même confirmation, car son message EST
     * bien reçu.
     */
    public function test_a_message_survives_a_dead_mailer(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'equipe@qrid.sn']);

        $this->messagerieEnPanne();

        $this->post(route('contact.store'), $this->messageValide())
            ->assertRedirect(route('home').'#contact')
            ->assertSessionHas('contact.envoye');

        $this->assertDatabaseCount('contact_messages', 1);

        // L'échec n'est pas caché pour autant : il est consigné.
        $this->assertDatabaseHas('mail_logs', ['status' => 'failed']);
    }

    /**
     * L'ADRESSE DE RÉPONSE EST CELLE DU CLIENT.
     *
     * Sans elle, répondre exigerait de recopier l'adresse à la main depuis le
     * corps du message. C'est tout l'intérêt de la transmission par e-mail.
     */
    public function test_the_reply_goes_back_to_the_sender(): void
    {
        Mail::fake();
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'equipe@qrid.sn']);

        $this->post(route('contact.store'), $this->messageValide());

        Mail::assertSent(ContactMail::class, function (ContactMail $m) {
            return $m->hasReplyTo('awa@exemple.sn');
        });
    }

    /** Le sujet porte le motif : une boîte de support se trie sur les sujets. */
    public function test_the_subject_carries_the_reason(): void
    {
        Mail::fake();
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->post(route('contact.store'), $this->messageValide());

        Mail::assertSent(ContactMail::class, function (ContactMail $m) {
            $sujet = $m->envelope()->subject;

            return str_contains($sujet, '[Contact]')
                && str_contains($sujet, 'cartes imprimées');
        });
    }

    /** Sans destinataire configuré, le message est tout de même conservé. */
    public function test_a_message_is_kept_even_with_nobody_to_notify(): void
    {
        Mail::fake();

        // Aucun administrateur, aucune adresse de support.
        config(['landing.support.email' => null]);

        $this->post(route('contact.store'), $this->messageValide())
            ->assertSessionHas('contact.envoye');

        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertNothingSent();
    }

    /** L'adresse de support configurée l'emporte sur les comptes admin. */
    public function test_a_configured_support_address_wins(): void
    {
        Mail::fake();

        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'perso@qrid.sn']);
        config(['landing.support.email' => 'support@qrid.sn']);

        $this->post(route('contact.store'), $this->messageValide());

        Mail::assertSent(ContactMail::class, fn (ContactMail $m) => $m->hasTo('support@qrid.sn')
            && ! $m->hasTo('perso@qrid.sn'));
    }

    // =======================================================================
    // CE QUI DOIT ÊTRE REFUSÉ
    // =======================================================================

    /**
     * LE PIÈGE À ROBOTS.
     *
     * Un champ masqué que personne ne voit et qu'aucun humain ne remplit. Les
     * robots remplissent tout ce qu'ils trouvent : un champ non vide vaut donc
     * rejet, sans base de données ni e-mail.
     */
    public function test_a_filled_honeypot_is_refused(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->messageValide([
            'site_web' => 'https://spam.example',
        ]))->assertSessionHasErrors('site_web');

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    /** @return array<string, array{array<string,mixed>, string}> */
    public static function saisiesInvalides(): array
    {
        return [
            'sans nom' => [['name' => ''], 'name'],
            'sans adresse' => [['email' => ''], 'email'],
            'adresse invalide' => [['email' => 'pas-une-adresse'], 'email'],
            'motif inconnu' => [['subject' => 'autre-chose'], 'subject'],
            'message vide' => [['message' => ''], 'message'],
            'message trop court' => [['message' => 'Rappelez-moi'], 'message'],
        ];
    }

    #[DataProvider('saisiesInvalides')]
    public function test_an_invalid_submission_is_refused(array $remplace, string $champ): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->messageValide($remplace))
            ->assertSessionHasErrors($champ);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    /**
     * UN MESSAGE TROP COURT EST REFUSÉ, avec une raison utile.
     *
     * « Rappelez-moi » ne peut pas être traité : il faut réécrire pour
     * demander de quoi il s'agit, ce qui perd tout le monde. Le message
     * d'erreur doit donc dire quoi faire, pas seulement constater.
     */
    public function test_the_too_short_message_explains_what_to_do(): void
    {
        $this->post(route('contact.store'), $this->messageValide(['message' => 'Info']));

        $this->assertStringContainsString(
            'vingt caractères',
            (string) session('errors')?->first('message')
        );
    }

    /** La saisie est reposée après une erreur : on ne fait pas tout retaper. */
    public function test_a_refused_submission_keeps_what_was_typed(): void
    {
        $this->from(route('home'))
            ->post(route('contact.store'), $this->messageValide(['message' => '']))
            ->assertSessionHasInput('name', 'Awa Ndiaye')
            ->assertSessionHasInput('email', 'awa@exemple.sn');
    }

    /**
     * LE FORMULAIRE EST LIMITÉ EN CADENCE.
     *
     * Ouvert à n'importe qui, sans compte, il deviendrait sinon un relais
     * d'envoi vers notre propre boîte — et la réputation de l'expéditeur en
     * pâtirait, celle-là même dont dépendent les liens de réinitialisation.
     */
    public function test_the_form_is_rate_limited(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), $this->messageValide([
                'email' => "envoi{$i}@exemple.sn",
            ]))->assertRedirect();
        }

        $this->post(route('contact.store'), $this->messageValide(['email' => 'sixieme@exemple.sn']))
            ->assertStatus(429);

        $this->assertDatabaseCount('contact_messages', 5);
    }

    // =======================================================================
    // L'ÉCRAN
    // =======================================================================

    /** La section existe sur la page d'accueil, avec son ancre. */
    public function test_the_landing_page_carries_the_contact_form(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('id="contact"', $html);
        $this->assertStringContainsString(route('contact.store'), $html);
        $this->assertStringContainsString('name="_token"', $html);
    }

    /** Un client connecté retrouve son nom et son adresse déjà remplis. */
    public function test_a_signed_in_client_finds_their_details_prefilled(): void
    {
        $user = User::factory()->create([
            'name' => 'Mouhamed Dione',
            'email' => 'mouhamed@exemple.sn',
            'email_verified_at' => now(),
        ]);

        $html = $this->actingAs($user)->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('value="Mouhamed Dione"', $html);
        $this->assertStringContainsString('value="mouhamed@exemple.sn"', $html);
    }

    /** La confirmation s'affiche après un envoi réussi. */
    public function test_the_confirmation_is_shown_after_sending(): void
    {
        Mail::fake();
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->followingRedirects()
            ->post(route('contact.store'), $this->messageValide())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Message reçu', $html);

        // Annoncée aux lecteurs d'écran : sans role="status", quelqu'un qui
        // n'a pas la page sous les yeux renverrait son message.
        $this->assertStringContainsString('role="status"', $html);
    }

    // =======================================================================
    // LE BOUTON WHATSAPP
    // =======================================================================

    public function test_the_whatsapp_button_is_present_on_the_landing_page(): void
    {
        config(['landing.support.whatsapp' => '221773831364']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('wa-fab', false)
            ->assertSee('https://wa.me/221773831364', false);
    }

    public function test_the_whatsapp_button_is_present_in_the_client_area(): void
    {
        config(['landing.support.whatsapp' => '221773831364']);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('wa-fab', false);
    }

    /**
     * SANS NUMÉRO, PAS DE BOUTON.
     *
     * Un bouton d'aide qui mène à un numéro inexistant est pire que pas de
     * bouton : quelqu'un qui a déjà un problème en rencontre un second.
     */
    public function test_no_button_without_a_number(): void
    {
        config(['landing.support.whatsapp' => '']);

        $this->get(route('home'))->assertOk()->assertDontSee('wa-fab', false);
    }

    /**
     * LE NUMÉRO EST NETTOYÉ DE TOUT CE QUI N'EST PAS UN CHIFFRE.
     *
     * wa.me n'accepte rien d'autre : un « + » ou une espace produit un lien
     * qui s'ouvre sur une erreur WhatsApp au lieu d'une conversation.
     */
    public function test_the_number_is_cleaned_for_the_link(): void
    {
        config(['landing.support.whatsapp' => '+221 77 383 13 64']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://wa.me/221773831364', false);
    }

    // =======================================================================
    // LE MESSAGE PRÉ-REMPLI SUIT LA PAGE
    // =======================================================================

    /** Le texte réellement présent dans le lien WhatsApp d'une page. */
    private function messageWhatsApp(string $html): string
    {
        preg_match('/wa\.me\/\d+\?text=([^"]+)/', $html, $trouve);

        return $trouve ? urldecode($trouve[1]) : '';
    }

    /**
     * CHAQUE ÉCRAN PORTE SON PROPRE MESSAGE.
     *
     * « J'ai une question » oblige la personne à tout écrire au moment précis
     * où elle est bloquée, souvent sur un téléphone. Un message qui NOMME
     * l'écran épargne la saisie ET dit à l'équipe où elle était :
     * « je suis à l'étape 2 » se traite tout de suite, « j'ai un problème »
     * demande trois allers-retours.
     */
    public function test_every_screen_carries_its_own_prefilled_message(): void
    {
        config(['landing.support.whatsapp' => '221773831364']);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $messages = [
            'accueil' => $this->messageWhatsApp($this->get(route('home'))->getContent()),
            'connexion' => $this->messageWhatsApp($this->get(route('login'))->getContent()),
            'mot de passe' => $this->messageWhatsApp($this->get(route('password.request'))->getContent()),
            'tableau de bord' => $this->messageWhatsApp(
                $this->actingAs($user)->get(route('dashboard'))->getContent()
            ),
            'étape 1' => $this->messageWhatsApp(
                $this->actingAs($user)->get(route('profile.create.step1'))->getContent()
            ),
        ];

        foreach ($messages as $ecran => $texte) {
            $this->assertNotSame('', $texte, "L'écran « {$ecran} » n'a aucun message pré-rempli.");
        }

        $this->assertSame(
            count($messages),
            count(array_unique($messages)),
            'Deux écrans partagent le même message : l\'un des deux ne dit pas où se trouve la personne.'
        );
    }

    /** Le message nomme l'écran, en toutes lettres. */
    public function test_the_message_names_the_screen(): void
    {
        config(['landing.support.whatsapp' => '221773831364']);

        /*
         | L'ÉCRAN INVITÉ EST LU EN PREMIER, et ce n'est pas un détail de mise
         | en forme : `login` est derrière le middleware « guest ». Une fois
         | actingAs() appelé, il redirige vers le tableau de bord et la page
         | lue n'est plus celle qu'on croit.
         */
        $connexion = $this->messageWhatsApp($this->get(route('login'))->getContent());

        $this->assertStringContainsString('connecter', $connexion);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $etape2 = $this->messageWhatsApp(
            $this->actingAs($user)
                ->withSession(['profile_wizard' => ['completed' => [1], 'data' => []]])
                ->get(route('profile.create.step2'))->getContent()
        );

        $this->assertStringContainsString('étape 2', $etape2);
    }

    /**
     * LES ÉCRANS D'ACCÈS PORTENT LE BOUTON, et c'est là qu'il compte le plus.
     *
     * Quelqu'un qui ne peut pas se connecter n'a aucun autre canal pour le
     * dire : ni tableau de bord, ni formulaire derrière une session.
     */
    public function test_the_authentication_screens_carry_the_help_button(): void
    {
        config(['landing.support.whatsapp' => '221773831364']);

        foreach (['login', 'register', 'password.request'] as $ecran) {
            $this->get(route($ecran))
                ->assertOk()
                ->assertSee('wa-fab', false);
        }
    }

    /**
     * AUCUNE DONNÉE PERSONNELLE dans le message pré-rempli.
     *
     * Il part dans WhatsApp, hors de l'application, et se retrouve dans une
     * URL que le navigateur enregistre dans son historique. L'équipe
     * reconnaîtra la personne à son numéro.
     */
    public function test_the_prefilled_message_leaks_no_personal_data(): void
    {
        config(['landing.support.whatsapp' => '221773831364']);

        $user = User::factory()->create([
            'name' => 'Mouhamed Dione',
            'email' => 'mouhamed@exemple.sn',
            'email_verified_at' => now(),
        ]);

        $texte = $this->messageWhatsApp(
            $this->actingAs($user)->get(route('dashboard'))->getContent()
        );

        $this->assertStringNotContainsString('Mouhamed', $texte);
        $this->assertStringNotContainsString('mouhamed@exemple.sn', $texte);
    }

    /** Une page non listée reçoit un message neutre, jamais un message faux. */
    public function test_an_unlisted_page_falls_back_to_a_neutral_message(): void
    {
        $message = AideContextuelle::message('une.route.qui.nexiste.pas');

        $this->assertStringContainsString('besoin d\'aide', $message);
        $this->assertStringContainsString(config('app.name'), $message);
    }

    // =======================================================================
    // LE MOTIF DU FORMULAIRE
    // =======================================================================

    /** Le motif peut être présélectionné par l'URL. */
    public function test_the_contact_subject_can_be_preselected_by_url(): void
    {
        $html = $this->get(route('home').'?motif=commande')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<option value="commande"[^>]*selected/',
            $html,
            'Le motif demandé dans l\'URL n\'est pas présélectionné.'
        );
    }

    /**
     * Un motif inventé dans l'URL ne casse rien.
     *
     * L'URL est écrite par n'importe qui : une valeur inconnue doit retomber
     * sur le premier motif, jamais produire un <select> sans sélection ni une
     * option fantôme.
     */
    public function test_an_invented_subject_in_the_url_is_ignored(): void
    {
        $html = $this->get(route('home').'?motif=nimporte-quoi')->assertOk()->getContent();

        $this->assertStringNotContainsString('nimporte-quoi', $html);
        $this->assertMatchesRegularExpression('/<option value="information"[^>]*selected/', $html);
    }

    /** Le modèle expose l'état de traitement, pour le suivi côté équipe. */
    public function test_a_new_message_starts_untreated(): void
    {
        Mail::fake();
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->post(route('contact.store'), $this->messageValide());

        $message = ContactMessage::recents()->first();

        $this->assertFalse($message->estTraite());
        $this->assertSame(1, ContactMessage::enAttente()->count());
    }
}
