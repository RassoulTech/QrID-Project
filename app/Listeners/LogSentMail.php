<?php

namespace App\Listeners;

use App\Models\MailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

/**
 * Journalise chaque e-mail effectivement remis au transport, en base ET dans
 * un canal de log dédié. Permet de répondre à un client affirmant n'avoir
 * rien reçu : on retrouve la date, le destinataire, le type et le statut.
 */
class LogSentMail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        $to = implode(', ', array_map(
            fn ($address) => $address->getAddress(),
            $message->getTo()
        ));

        $mailable = $event->data['__laravel_mailable'] ?? null;

        Log::channel('mail')->info('E-mail envoyé', [
            'to' => $to,
            'subject' => $message->getSubject(),
            'mailable' => $mailable,
            'mailer' => config('mail.default'),
        ]);

        // Le journal en base ne doit jamais faire échouer un envoi réussi.
        try {
            MailLog::create([
                'recipient' => $to,
                'subject' => (string) $message->getSubject(),
                'mailable' => $mailable,
                'mailer' => config('mail.default'),
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('mail')->warning('Journal d\'envoi non enregistré', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
