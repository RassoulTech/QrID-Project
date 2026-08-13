<?php

namespace App\Listeners;

use App\Enums\MotifAlerte;
use App\Services\AdminNotifier;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

/**
 * Un job a épuisé toutes ses tentatives.
 *
 * RÉÉCRIT LE 13 AOÛT. Ce listener envoyait un Mail::raw() sans mise en forme,
 * vers registration.admin_email — une seconde liste de destinataires, distincte
 * de celle des cinq autres alertes. Deux conséquences, toutes deux mauvaises :
 * ajouter un administrateur ne l'abonnait pas à cette alerte-ci, et le message
 * ne ressemblait à rien de ce que l'équipe reçoit par ailleurs.
 *
 * Il passe désormais par AdminNotifier, comme les cinq autres motifs. Une
 * seule liste de destinataires, une seule mise en forme, un seul endroit à
 * corriger.
 *
 * L'ERREUR TECHNIQUE EST TRONQUÉE À 300 CARACTÈRES. Une trace complète rend
 * l'e-mail illisible sur téléphone et n'aide pas : ce message sert à SAVOIR
 * qu'il faut aller voir, pas à diagnostiquer. La trace entière est dans les
 * logs.
 */
class AlertOnJobFailed
{
    public function __construct(private AdminNotifier $equipe) {}

    public function handle(JobFailed $event): void
    {
        $erreur = $event->exception->getMessage();

        Log::error('Job en échec définitif', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'exception' => $erreur,
        ]);

        $this->equipe->alerter(
            MotifAlerte::TravailEnEchec,
            [
                'File' => (string) $event->job->getQueue(),
                'Connexion' => (string) $event->connectionName,
                'Tâche' => $event->job->resolveName(),
                'Erreur' => mb_substr($erreur, 0, 300),
                'Survenu le' => now()->translatedFormat('j F Y à H:i'),
            ],
        );
    }
}
