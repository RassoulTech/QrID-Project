<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Compteur de volume — NE BLOQUE JAMAIS UN ENVOI.
 *
 * Aucun filtrage de destinataire n'existe dans ce projet : tout e-mail adressé
 * à n'importe quel domaine part réellement. Ce listener se contente de compter
 * les envois par heure en environnement local et de journaliser une alerte
 * au-delà du seuil, à titre informatif (protection du quota Gmail).
 *
 * Il retourne toujours true : Laravel n'annule jamais l'envoi.
 */
class GuardOutgoingMail
{
    public function handle(MessageSending $event): bool
    {
        if (! app()->environment('local')) {
            return true;
        }

        $key = 'mail-hourly-count:'.now()->format('Y-m-d-H');
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addHour());

        $threshold = config('mail.hourly_alert_threshold', 100);

        if ($count > $threshold) {
            Log::channel('mail')->warning(
                'Volume d\'envoi élevé — alerte informative, aucun envoi bloqué',
                ['count_this_hour' => $count, 'threshold' => $threshold]
            );
        }

        return true; // jamais de blocage
    }
}
