<?php

namespace Tests\Feature\Notifications;

use App\Enums\MotifAlerte;
use App\Mail\AdminAlertMail;
use App\Mail\BaseMailable;
use App\Mail\ContactMail;
use App\Mail\PasswordChangedMail;
use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSucceededMail;
use App\Mail\ProfilePublishedMail;
use App\Mail\ProfileReminderMail;
use App\Mail\SubscriptionExpiredMail;
use App\Mail\SubscriptionExpiringMail;
use App\Mail\WelcomeMail;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * CHAQUE E-MAIL SE FABRIQUE VRAIMENT — version HTML ET version texte.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CE TEST NE FAIT PAS DOUBLE EMPLOI AVEC LES AUTRES
 * ═══════════════════════════════════════════════════════════════════════
 * `Mail::fake()` intercepte l'envoi AVANT le rendu : il constate qu'un message
 * a été demandé, jamais qu'il peut être écrit. Une faute dans un gabarit
 * Blade — une variable absente, une directive mal fermée — traverse donc
 * intacte tous les tests qui utilisent la fausse messagerie.
 *
 * Elle n'apparaîtrait qu'en production, au moment de l'envoi, sous la forme
 * d'une exception dans le parcours du client. Sur le reçu de paiement, cela
 * se produirait pile au retour de l'opérateur.
 *
 * On envoie donc ici POUR DE VRAI, via le transport « array » : rien ne part
 * sur le réseau, mais les deux gabarits sont compilés et rendus. Un
 * `@endcomponent` oublié fait tomber ce test, et lui seul.
 *
 * LA VERSION TEXTE COMPTE AUTANT QUE L'AUTRE. C'est elle que lisent les
 * clients de messagerie en mode dégradé, fréquent sur les téléphones d'entrée
 * de gamme — et c'est elle qu'aucun test de rendu HTML ne toucherait.
 */
class EveryMailRendersTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{BaseMailable}> */
    public static function messages(): array
    {
        $url = 'https://qrid.test/quelque-part';

        return [
            'bienvenue' => [new WelcomeMail(
                name: 'Awa Ndiaye',
                createUrl: $url,
                trialDays: 15,
                trialEndsAt: '28 août 2026',
            )],

            'bienvenue sans échéance' => [new WelcomeMail(
                name: 'Awa Ndiaye',
                createUrl: $url,
                trialDays: 15,
                trialEndsAt: null,
            )],

            'rappel 24 h' => [new ProfileReminderMail(
                name: 'Awa Ndiaye', activateUrl: $url, rang: 1,
            )],

            'rappel 72 h' => [new ProfileReminderMail(
                name: 'Awa Ndiaye', activateUrl: $url, rang: 2,
            )],

            'carte en ligne' => [new ProfilePublishedMail(
                name: 'Awa Ndiaye', publicUrl: $url, dashboardUrl: $url,
            )],

            'reçu de paiement' => [new PaymentSucceededMail(
                name: 'Awa Ndiaye',
                reference: 'FAKE-ABC123',
                montant: '2 500',
                moyen: 'Wave',
                formule: 'Standard',
                date: '13 août 2026 à 10:00',
                echeance: '12 septembre 2026',
                publicUrl: $url,
                dashboardUrl: $url,
            )],

            // Sans carte : le reçu doit rester valable et renvoyer à l'espace.
            'reçu sans carte' => [new PaymentSucceededMail(
                name: 'Awa Ndiaye',
                reference: 'FAKE-ABC123',
                montant: '2 500',
                moyen: 'Wave',
                formule: 'Standard',
                date: '13 août 2026 à 10:00',
                echeance: null,
                publicUrl: null,
                dashboardUrl: $url,
            )],

            'paiement échoué' => [new PaymentFailedMail(
                name: 'Awa Ndiaye', montant: '2 500', formule: 'Standard', retryUrl: $url,
            )],

            // Les quatre paliers empruntent trois branches de rédaction
            // différentes : chacune doit être compilée au moins une fois.
            'échéance J-7' => [new SubscriptionExpiringMail(
                name: 'Awa', joursRestants: 7, echeance: '20 août 2026',
                formule: 'Standard', renewUrl: $url,
            )],

            'échéance J-1' => [new SubscriptionExpiringMail(
                name: 'Awa', joursRestants: 1, echeance: '14 août 2026',
                formule: 'Standard', renewUrl: $url,
            )],

            'échéance jour même' => [new SubscriptionExpiringMail(
                name: 'Awa', joursRestants: 0, echeance: '13 août 2026',
                formule: 'Standard', renewUrl: $url,
            )],

            'abonnement expiré' => [new SubscriptionExpiredMail(
                name: 'Awa', echeance: '10 août 2026', renewUrl: $url, publicUrl: $url,
            )],

            'abonnement expiré sans carte' => [new SubscriptionExpiredMail(
                name: 'Awa', echeance: '10 août 2026', renewUrl: $url, publicUrl: null,
            )],

            'mot de passe modifié' => [new PasswordChangedMail(
                name: 'Awa', date: '13 août 2026 à 10:00', ip: '41.82.10.5', resetUrl: $url,
            )],

            'mot de passe modifié sans IP' => [new PasswordChangedMail(
                name: 'Awa', date: '13 août 2026 à 10:00', ip: null, resetUrl: $url,
            )],

            'alerte urgente' => [new AdminAlertMail(
                motif: MotifAlerte::PaiementEchoue,
                lignes: ['Client' => 'Awa Ndiaye', 'Montant' => '2 500 FCFA'],
                url: $url,
            )],

            'alerte informative' => [new AdminAlertMail(
                motif: MotifAlerte::CompteConfirme,
                lignes: ['Client' => 'Awa Ndiaye'],
                url: $url,
            )],

            // Sans lien : le bouton disparaît, le message doit tenir debout.
            'alerte sans lien' => [new AdminAlertMail(
                motif: MotifAlerte::TravailEnEchec,
                lignes: ['File' => 'mail', 'Erreur' => 'délai dépassé'],
                url: null,
            )],

        ];
    }

    /*
     |--------------------------------------------------------------------------
     | LE MESSAGE DE CONTACT EST TRAITÉ À PART, ET IL LE FAUT
     |--------------------------------------------------------------------------
     | Un fournisseur de données PHPUnit est STATIQUE : il s'exécute avant que
     | l'application ne démarre. Ni `now()`, ni un modèle Eloquent n'y sont
     | disponibles — les y placer fait échouer la collecte des tests, et PHPUnit
     | annonce alors « aucun test trouvé », ce qui masque tout le fichier.
     |
     | Les autres messages n'ont que des chaînes en paramètre : eux passent.
     */

    /**
     * LE MESSAGE DE CONTACT SE FABRIQUE — défaut RÉEL, corrigé le 13 août.
     *
     * Son gabarit appelait `$motif` en comptant sur la méthode motif(). Laravel
     * ne transmet à la vue que les PROPRIÉTÉS publiques d'un Mailable, jamais
     * ses méthodes : la variable n'existait pas, le rendu levait « Undefined
     * variable $motif », et AUCUN message de contact ne partait.
     *
     * Le défaut a traversé toute la suite parce que Mail::fake() intercepte
     * AVANT le rendu. Il n'est apparu qu'à l'envoi réel — c'est-à-dire chez le
     * client, sur un message dont personne ne remarque l'absence.
     */
    public function test_the_contact_mail_builds_both_bodies(): void
    {
        config(['mail.default' => 'array']);

        $contact = new ContactMessage([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '+221 77 383 13 64',
            'subject' => 'commande',
            'message' => "Bonjour,\nJe souhaite commander cinquante cartes.",
        ]);
        $contact->created_at = now();

        Mail::to('equipe@qrid.sn')->send(new ContactMail($contact));

        $corps = Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getOriginalMessage();

        $html = (string) $corps->getHtmlBody();
        $texte = (string) $corps->getTextBody();

        $this->assertNotEmpty($html);
        $this->assertNotEmpty($texte);

        // Le motif est bien résolu — c'est exactement ce qui manquait.
        $this->assertStringContainsString('cartes imprimées', $html);
        $this->assertStringContainsString('cartes imprimées', $texte);

        // Et le message du client y figure, retours à la ligne conservés.
        $this->assertStringContainsString('cinquante cartes', $html);

        // L'adresse de réponse est celle du client : c'est tout l'intérêt.
        $this->assertNotEmpty($corps->getReplyTo());
        $this->assertSame('awa@exemple.sn', $corps->getReplyTo()[0]->getAddress());
    }

    /** Sans téléphone ni compte, deux lignes disparaissent — et rien ne casse. */
    public function test_a_minimal_contact_mail_still_builds(): void
    {
        config(['mail.default' => 'array']);

        $contact = new ContactMessage([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'subject' => 'information',
            'message' => 'Bonjour, une question.',
        ]);

        Mail::to('equipe@qrid.sn')->send(new ContactMail($contact));

        $corps = Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getOriginalMessage();

        $this->assertNotEmpty($corps->getHtmlBody());
        $this->assertNotEmpty($corps->getTextBody());
    }

    #[DataProvider('messages')]
    public function test_a_mailable_builds_both_its_html_and_text_bodies(BaseMailable $message): void
    {
        // Transport « array » : rien ne sort, mais tout est rendu.
        config(['mail.default' => 'array']);

        Mail::to('destinataire@qrid.sn')->send($message);

        $envoyes = Mail::mailer('array')->getSymfonyTransport()->messages();

        $this->assertCount(1, $envoyes);

        /*
         | getOriginalMessage() ET NON getMessage().
         |
         | Symfony conserve deux formes : l'objet Email construit par Laravel,
         | et sa sérialisation brute. getMessage() rend la seconde — un
         | RawMessage, qui n'expose ni corps HTML ni corps texte séparément.
         | C'est l'original qu'il faut interroger pour vérifier les deux
         | versions indépendamment.
         */
        $corps = $envoyes[0]->getOriginalMessage();

        $this->assertNotEmpty($corps->getHtmlBody(), 'La version HTML est vide.');
        $this->assertNotEmpty($corps->getTextBody(), 'La version texte est vide.');

        // Un sujet vide passe les filtres anti-spam bien plus mal qu'un sujet
        // médiocre, et ne dit rien dans une liste de messages.
        $this->assertNotEmpty($corps->getSubject());
    }

    /**
     * AUCUN E-MAIL NE DOIT LAISSER FUIR UNE VARIABLE NON RÉSOLUE.
     *
     * Une accolade Blade oubliée sort telle quelle dans le message : le client
     * lit « {{ $name }} » et conclut, à juste titre, que le produit est bâclé.
     */
    #[DataProvider('messages')]
    public function test_no_unresolved_blade_expression_reaches_the_reader(BaseMailable $message): void
    {
        config(['mail.default' => 'array']);

        Mail::to('destinataire@qrid.sn')->send($message);

        $corps = Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getOriginalMessage();

        foreach (['getHtmlBody', 'getTextBody'] as $partie) {
            $texte = (string) $corps->{$partie}();

            $this->assertStringNotContainsString('{{', $texte);
            $this->assertStringNotContainsString('@if', $texte);
            $this->assertStringNotContainsString('@endcomponent', $texte);
        }
    }
}
