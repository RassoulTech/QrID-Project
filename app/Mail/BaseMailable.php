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
 * Porte la politique commune : mise en file (jamais d'envoi synchrone dans une
 * requête), tentatives, backoff, timeout, et journalisation des échecs
 * définitifs. Les gabarits partagent le layout emails.layout pour l'identité
 * visuelle — aucun duplicata de mise en page.
 */
abstract class BaseMailable extends Mailable implements ShouldQueue
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
