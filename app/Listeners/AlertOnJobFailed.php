<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Déclenché quand un job échoue définitivement (toutes tentatives épuisées).
 * On journalise et on alerte l'admin (envoi synchrone, best-effort).
 */
class AlertOnJobFailed
{
    public function handle(JobFailed $event): void
    {
        Log::error('Job en échec définitif', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'exception' => $event->exception->getMessage(),
        ]);

        $admin = config('registration.admin_email');

        if (! $admin) {
            return;
        }

        try {
            $body = "Un job a échoué définitivement.\n"
                .'File : '.$event->job->getQueue()."\n"
                .'Erreur : '.$event->exception->getMessage();

            Mail::raw($body, function ($m) use ($admin) {
                $m->to($admin)->subject('[Alerte] Job en échec — '.config('app.name'));
            });
        } catch (\Throwable $e) {
            Log::error('Alerte admin non envoyée', ['message' => $e->getMessage()]);
        }
    }
}
