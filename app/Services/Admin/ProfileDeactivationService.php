<?php

namespace App\Services\Admin;

use App\Models\AdminAction;
use App\Models\Profile;
use App\Support\AdminActionType;
use Illuminate\Support\Facades\DB;

/**
 * Désactivation d'un profil par l'administration.
 *
 * SEULE PRISE DE L'ADMINISTRATION SUR UN PROFIL. Il n'existe volontairement
 * aucune méthode d'édition ici : couper, oui ; réécrire le contenu de
 * quelqu'un d'autre, jamais. C'est une règle métier, pas une limitation
 * technique — la mettre en évidence dans le seul service qui touche aux
 * profils est le meilleur endroit pour qu'elle ne se perde pas.
 *
 * La réactivation ne republie PAS le profil : elle lève la sanction et
 * rend la main au client, qui republie s'il le souhaite. Republier
 * d'autorité remettrait en ligne un contenu que son auteur a peut-être
 * entre-temps décidé de retirer.
 */
class ProfileDeactivationService
{
    public function desactiver(Profile $profil, string $motif): void
    {
        DB::transaction(function () use ($profil, $motif) {
            $profil->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_reason' => $motif,
            ])->save();

            AdminAction::log(AdminActionType::DESACTIVATION_PROFIL, $profil, $motif);
        });
    }

    public function reactiver(Profile $profil, string $motif): void
    {
        DB::transaction(function () use ($profil, $motif) {
            // `is_active` reste à false : la main revient au client.
            $profil->forceFill([
                'deactivated_at' => null,
                'deactivated_reason' => null,
            ])->save();

            AdminAction::log(AdminActionType::REACTIVATION_PROFIL, $profil, $motif);
        });
    }
}
