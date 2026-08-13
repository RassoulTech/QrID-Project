<?php

namespace App\Listeners;

use App\Enums\MotifAlerte;
use App\Events\ProfileCreated;
use App\Services\AdminNotifier;

/**
 * Carte enregistrée. ALERTE ÉQUIPE UNIQUEMENT — aucun e-mail au client.
 *
 * Le client vient de valider la dernière étape et regarde l'écran d'aperçu de
 * sa propre carte : lui écrire « votre carte est créée » à cet instant précis
 * n'apprend rien à personne. Le seul message qui a du sens dans cet état est
 * le RAPPEL, s'il ne publie pas — et il part vingt-quatre heures plus tard.
 */
class AnnounceProfileCreated
{
    public function __construct(private AdminNotifier $equipe) {}

    public function handle(ProfileCreated $event): void
    {
        $profile = $event->profile;

        $this->equipe->alerter(
            MotifAlerte::ProfilCree,
            [
                'Client' => $profile->user?->name ?? '—',
                'Carte' => trim($profile->first_name.' '.$profile->last_name),
                'Fonction' => $profile->job_title ?: '—',
                'Adresse publique' => '/p/'.$profile->slug,
                'État' => $profile->is_active ? 'en ligne' : 'non publiée',
            ],
            route('admin.profiles.index'),
        );
    }
}
