<?php

namespace App\Support;

use App\Mail\BaseMailable;
use App\Models\MailLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * ENVOI D'INFORMATION — celui qui ne doit jamais casser la page.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX FAMILLES D'E-MAILS, DEUX COMPORTEMENTS OPPOSÉS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. LE COURRIER QUI PORTE LE PARCOURS — lien de confirmation d'inscription,
 *    lien de réinitialisation. Sans lui, l'utilisateur est bloqué. Son échec
 *    DOIT remonter : mieux vaut un message d'erreur honnête qu'un « c'est
 *    envoyé » qui ment. Ces deux-là n'utilisent pas cette classe ; ils
 *    appellent Mail::send() et laissent l'exception passer.
 *
 * 2. LE COURRIER QUI INFORME — bienvenue, reçu de paiement, échéance qui
 *    approche. Il accompagne une action déjà réussie. S'il échoue, l'action
 *    reste réussie : le paiement est encaissé, la carte est publiée. Faire
 *    remonter cette panne transformerait un succès en erreur 500 sous les
 *    yeux du client, qui croirait avoir payé pour rien.
 *
 * Toute cette classe sert à tenir la seconde famille. Elle avale l'échec,
 * mais NE LE CACHE JAMAIS : chaque panne laisse une ligne dans mail_logs,
 * visible sur l'écran « État système ». Silencieux pour l'utilisateur,
 * bruyant pour l'exploitant — c'est le seul partage acceptable.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI L'ENVOI EST IMMÉDIAT ET NON EN FILE
 * ═══════════════════════════════════════════════════════════════════════
 * Aucun worker n'exécute queue:work en production : le plan Render n'héberge
 * qu'un service web. Un message mis en file y resterait indéfiniment, sans
 * erreur et sans trace — la panne qui a coûté plusieurs jours.
 *
 * Le jour où un worker existera, c'est ICI, et nulle part ailleurs, qu'il
 * faudra remplacer send() par queue() : aucun listener n'a à le savoir.
 */
final class Courrier
{
    /**
     * Envoie un e-mail d'information. Rend true si le transport l'a accepté.
     *
     * @param  string|array<int, string>  $destinataire
     */
    public static function informer(string|array $destinataire, BaseMailable $message): bool
    {
        $adresses = array_filter((array) $destinataire);

        if ($adresses === []) {
            return false;   // rien à faire, et surtout rien à signaler
        }

        try {
            Mail::to($adresses)->send($message);

            return true;
        } catch (Throwable $e) {
            self::consigner($adresses, $message, $e);

            return false;
        }
    }

    /**
     * ENVOIE UN E-MAIL QUE QUELQU'UN ATTEND. L'échec est tracé, PUIS RELANCÉ.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI DEUX MÉTHODES, ET NON UNE
     * ═══════════════════════════════════════════════════════════════════
     * `informer()` avale l'échec : un récapitulatif qui ne part pas ne doit
     * pas casser l'action de celui qui l'a déclenchée sans le savoir.
     *
     * Mais certains messages sont la RÉPONSE ATTENDUE à un geste précis :
     * le lien de confirmation d'inscription, le lien de réinitialisation.
     * Les avaler afficherait « vérifiez votre boîte » à quelqu'un dont le
     * message n'est jamais parti. Il attendrait, puis recommencerait, puis
     * conclurait que le produit ne fonctionne pas — et rien, nulle part,
     * n'aurait signalé la panne.
     *
     * Ceux-là doivent échouer BRUYAMMENT. Mais bruyamment ne veut pas dire
     * sans trace : l'exception remonte, ET la ligne part dans `mail_logs`,
     * où l'écran « État système » la lit.
     *
     * ═══════════════════════════════════════════════════════════════════
     * CE QUE CETTE MÉTHODE UNIFIE
     * ═══════════════════════════════════════════════════════════════════
     * Trois envois contournaient déjà `Courrier`, chacun pour cette raison,
     * et chacun à sa façon :
     *
     *   User::sendPasswordResetNotification   journalisait puis relançait
     *   RegistrationService (deux envois)     ne faisait NI l'un NI l'autre
     *
     * Les deux e-mails d'inscription — les plus décisifs du produit, ceux
     * sans lesquels aucun compte ne peut exister — tombaient donc sans
     * laisser la moindre trace sur l'écran d'état.
     */
    public static function exiger(string|array $destinataire, BaseMailable $message): void
    {
        $adresses = array_filter((array) $destinataire);

        if ($adresses === []) {
            return;   // rien à faire, et surtout rien à signaler
        }

        try {
            Mail::to($adresses)->send($message);
        } catch (Throwable $e) {
            self::consigner($adresses, $message, $e);

            // Relancée telle quelle : l'appelant décide quoi en faire, et
            // la remplacer par une exception maison perdrait le message du
            // transport, qui est le seul à dire ce qui a réellement cloché.
            throw $e;
        }
    }

    /**
     * Trace de l'échec — en base d'abord, car c'est elle que l'écran
     * « État système » interroge, puis dans le canal de log dédié.
     *
     * L'écriture est elle-même protégée : si la base est le problème,
     * l'incapacité à journaliser ne doit pas relancer une exception depuis
     * le bloc qui existe précisément pour en absorber une.
     *
     * @param  array<int, string>  $adresses
     */
    private static function consigner(array $adresses, BaseMailable $message, Throwable $e): void
    {
        $sujet = self::sujet($message);

        Log::channel('mail')->error('E-mail non parti', [
            'to' => $adresses,
            'mailable' => $message::class,
            'error' => $e->getMessage(),
        ]);

        try {
            MailLog::create([
                'recipient' => implode(', ', $adresses),
                'subject' => $sujet,
                'mailable' => $message::class,
                'mailer' => config('mail.default'),
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
                'sent_at' => null,
            ]);
        } catch (Throwable $interne) {
            Log::channel('mail')->warning('Journal d\'échec non enregistré', [
                'message' => $interne->getMessage(),
            ]);
        }
    }

    /**
     * Le sujet réel du message, pour que le journal soit lisible.
     *
     * envelope() construit le sujet à partir des propriétés du Mailable ; il
     * peut lever si l'une manque. Dans ce cas on retombe sur le nom court de
     * la classe : une ligne de journal imparfaite vaut mieux qu'une seconde
     * exception à l'endroit même où l'on traite la première.
     */
    private static function sujet(BaseMailable $message): string
    {
        try {
            return $message->envelope()->subject ?? class_basename($message);
        } catch (Throwable) {
            return class_basename($message);
        }
    }
}
