<?php

namespace App\Listeners;

use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Déclenché par `queue:monitor` quand une file dépasse son seuil (50).
 * Alerte l'admin sans dépendre de la file elle-même (envoi synchrone, best-effort).
 */
class AlertOnQueueBusy
{
    public function handle(QueueBusy $event): void
    {
        Log::warning('File d\'attente engorgée', [
            'connection' => $event->connection,
            'queue' => $event->queue,
            'size' => $event->size,
        ]);

        $this->notifyAdmin(
            "File « {$event->queue} » engorgée : {$event->size} jobs en attente."
        );
    }

    private function notifyAdmin(string $message): void
    {
        $admin = config('registration.admin_email');

        if (! $admin) {
            return;
        }

        try {
            Mail::raw($message, function ($m) use ($admin) {
                $m->to($admin)->subject('[Alerte] File d\'attente — '.config('app.name'));
            });
        } catch (\Throwable $e) {
            Log::error('Alerte admin non envoyée', ['message' => $e->getMessage()]);
        }
    }
}
