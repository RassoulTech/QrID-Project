<?php

namespace Tests\Feature;

use App\Mail\ConfirmRegistrationMail;
use App\Mail\WelcomeMail;
use App\Models\MailLog;
use App\Support\Courrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * LES DEUX FAÇONS D'ENVOYER UN E-MAIL, ET POURQUOI IL EN FAUT DEUX.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE CHOIX N'EST PAS TECHNIQUE, IL EST MÉTIER
 * ═══════════════════════════════════════════════════════════════════════════
 * `informer()` avale l'échec. Un récapitulatif ou une alerte de confort qui
 * ne part pas ne doit pas casser l'action de celui qui l'a déclenchée sans
 * même le savoir — il a publié sa carte, il n'a pas demandé un e-mail.
 *
 * `exiger()` le relance. Certains messages sont la RÉPONSE ATTENDUE à un
 * geste précis : le lien de confirmation d'inscription, le lien de
 * réinitialisation. Les avaler afficherait « vérifiez votre boîte » à
 * quelqu'un dont le message n'est jamais parti. Il attendrait, puis
 * recommencerait, puis conclurait que le produit ne fonctionne pas.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * MAIS LES DEUX LAISSENT UNE TRACE
 * ═══════════════════════════════════════════════════════════════════════════
 * C'est le point qui manquait. Avant, les deux e-mails d'inscription — les
 * plus décisifs du produit, ceux sans lesquels aucun compte ne peut exister
 * — partaient hors de tout dispositif : en cas de panne SMTP, ni ligne dans
 * `mail_logs`, ni rien sur l'écran « État système ».
 *
 * Silencieux pour l'utilisateur ou bruyant selon le cas ; bruyant pour
 * l'exploitant, toujours.
 */
class CourrierTest extends TestCase
{
    use RefreshDatabase;

    /** Fait échouer le transport, comme un SMTP indisponible. */
    private function transportEnPanne(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP indisponible'));
    }

    // =======================================================================
    // informer() — le message de confort
    // =======================================================================

    public function test_informer_reports_success(): void
    {
        Mail::fake();

        $this->assertTrue(Courrier::informer('client@exemple.test', new WelcomeMail('Awa', 'https://qrid.test/carte', 14)));
    }

    /**
     * L'ÉCHEC EST AVALÉ : l'action de l'utilisateur n'est pas cassée par un
     * e-mail qu'il n'a pas demandé.
     */
    public function test_informer_swallows_a_transport_failure(): void
    {
        $this->transportEnPanne();

        $parti = Courrier::informer('client@exemple.test', new WelcomeMail('Awa', 'https://qrid.test/carte', 14));

        $this->assertFalse($parti, 'informer() doit rendre false, jamais lever.');
    }

    /** Avalé pour l'utilisateur, mais jamais caché à l'exploitant. */
    public function test_informer_still_records_the_failure(): void
    {
        $this->transportEnPanne();

        Courrier::informer('client@exemple.test', new WelcomeMail('Awa', 'https://qrid.test/carte', 14));

        $trace = MailLog::first();

        $this->assertNotNull($trace, "L'échec n'a laissé aucune trace : l'écran « État système » ne le verra jamais.");
        $this->assertSame('failed', $trace->status);
        $this->assertStringContainsString('client@exemple.test', $trace->recipient);
    }

    // =======================================================================
    // exiger() — le message qu'on attend devant sa boîte
    // =======================================================================

    /**
     * LE CŒUR : l'échec REMONTE, au lieu d'afficher « vérifiez votre boîte »
     * à quelqu'un dont le message n'est jamais parti.
     */
    public function test_exiger_rethrows_a_transport_failure(): void
    {
        $this->transportEnPanne();

        $this->expectException(RuntimeException::class);

        Courrier::exiger('client@exemple.test', new ConfirmRegistrationMail(
            'Awa', 'https://qrid.test/confirmation/jeton', 60, 'client@exemple.test',
        ));
    }

    /**
     * ET IL LAISSE UNE TRACE AVANT DE REMONTER.
     *
     * C'est ce qui manquait aux deux envois d'inscription : ils levaient
     * sans rien consigner. La panne se voyait à l'écran de l'utilisateur,
     * jamais dans les journaux de l'exploitant.
     */
    public function test_exiger_records_the_failure_before_rethrowing(): void
    {
        $this->transportEnPanne();

        try {
            Courrier::exiger('client@exemple.test', new ConfirmRegistrationMail(
                'Awa', 'https://qrid.test/confirmation/jeton', 60, 'client@exemple.test',
            ));
        } catch (RuntimeException) {
            // attendu
        }

        $this->assertNotNull(MailLog::first(),
            'exiger() a relancé sans consigner : la panne ne laisse aucune trace exploitable.');
    }

    public function test_exiger_sends_when_the_transport_works(): void
    {
        Mail::fake();

        Courrier::exiger('client@exemple.test', new ConfirmRegistrationMail(
            'Awa', 'https://qrid.test/confirmation/jeton', 60, 'client@exemple.test',
        ));

        Mail::assertSent(ConfirmRegistrationMail::class);
        $this->assertNull(MailLog::first(), 'Un envoi réussi ne doit pas produire de ligne d\'échec.');
    }

    // =======================================================================
    // LE CAS VIDE — commun aux deux
    // =======================================================================

    /**
     * Aucune adresse : rien à faire, et surtout rien à signaler.
     *
     * Un profil sans e-mail est un état normal, pas une anomalie. Lever ici
     * remplirait les journaux d'incidents qui n'en sont pas.
     */
    public function test_no_recipient_is_not_an_incident(): void
    {
        Mail::fake();

        $this->assertFalse(Courrier::informer([], new WelcomeMail('Awa', 'https://qrid.test/carte', 14)));

        Courrier::exiger('', new ConfirmRegistrationMail('Awa', 'https://qrid.test/x', 60, ''));

        Mail::assertNothingSent();
        $this->assertNull(MailLog::first());
    }
}
