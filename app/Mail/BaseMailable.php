<?php

namespace App\Mail;

use App\Models\MailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Classe de base de TOUS les e-mails du produit.
 *
 * Porte la politique commune : tentatives, backoff, délai d'exécution,
 * journalisation des échecs définitifs. Les gabarits partagent le layout
 * emails.layout pour l'identité visuelle — aucun duplicata de mise en page.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE N'IMPLÉMENTE PLUS ShouldQueue, ET C'EST LE POINT DE CE FICHIER
 * ═══════════════════════════════════════════════════════════════════════
 * Un Mailable qui déclare ShouldQueue est mis en file PAR LARAVEL LUI-MÊME,
 * quelle que soit la méthode d'envoi. Même `Mail::send()` le met en file.
 * La classe imposait donc la file à tout le produit.
 *
 * Or aucun worker n'exécute `queue:work` : le plan gratuit de Render
 * n'héberge qu'un service web. Les messages partaient dans la table `jobs`
 * et n'en ressortaient jamais — SANS AUCUNE ERREUR.
 *
 * Deux parcours étaient morts en production, et personne ne pouvait le voir :
 *
 *   · la réinitialisation de mot de passe — la page annonçait « lien
 *     envoyé », rien n'arrivait ;
 *   · la CONFIRMATION D'INSCRIPTION — donc plus aucune création de compte.
 *
 * LE CHOIX D'ENVOYER OU DE DIFFÉRER APPARTIENT À L'APPELANT, pas au message.
 * `Mail::send()` part tout de suite ; `Mail::queue()` diffère, pour qui le
 * demande explicitement. Le jour où un worker existera, les e-mails de
 * confort — rappels, récapitulatifs — repasseront en file par `queue()`,
 * sans qu'il faille toucher à cette classe.
 *
 * Les propriétés $tries, $timeout et backoff() restent utiles : elles
 * s'appliquent dès qu'un message EST mis en file.
 */
abstract class BaseMailable extends Mailable
{
    use Queueable, SerializesModels;

    /** Tentatives avant échec définitif. */
    public int $tries = 3;

    /** Délai max d'exécution d'un envoi (secondes). */
    public int $timeout = 30;

    /** Destinataire mémorisé pour la journalisation d'échec. */
    public string $recipient = '';

    /** Backoff progressif entre tentatives : 10 s, 30 s, 60 s. */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Échec définitif : journalisé en base et en log pour permettre
     * une relance manuelle et une réponse au client.
     */
    public function failed(Throwable $e): void
    {
        Log::channel('mail')->error('Échec définitif d\'envoi', [
            'mailable' => static::class,
            'recipient' => $this->recipient,
            'message' => $e->getMessage(),
        ]);

        try {
            MailLog::create([
                'recipient' => $this->recipient,
                'subject' => static::class,
                'mailable' => static::class,
                'mailer' => config('mail.default'),
                'status' => 'failed',
                'error' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        } catch (Throwable $inner) {
            Log::channel('mail')->warning('Journal d\'échec non enregistré', [
                'message' => $inner->getMessage(),
            ]);
        }
    }
}
