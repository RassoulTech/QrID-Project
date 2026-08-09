<?php

namespace App\Services\Admin;

use App\Models\AdminAction;
use App\Models\User;
use App\Support\AdminActionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blocage et déblocage d'un compte client.
 *
 * TROIS EFFETS, indissociables — d'où un service plutôt que trois lignes dans
 * le contrôleur :
 *
 *   1. le compte est marqué bloqué, avec son motif et son horodatage ;
 *   2. ses sessions ouvertes sont détruites ;
 *   3. l'action est journalisée avec son auteur.
 *
 * Le tout dans une transaction : un compte marqué bloqué sans trace au
 * journal serait une sanction sans auteur ni motif.
 */
class ClientBlockService
{
    /**
     * @param  string  $motif  Obligatoire. Validé en amont par la requête de formulaire.
     */
    public function bloquer(User $client, string $motif): void
    {
        DB::transaction(function () use ($client, $motif) {
            $client->forceFill([
                'is_blocked' => true,
                'blocked_at' => now(),
                'blocked_reason' => $motif,
            ])->save();

            $this->detruireLesSessions($client);

            AdminAction::log(AdminActionType::BLOCAGE_COMPTE, $client, $motif);
        });
    }

    /**
     * Le déblocage exige aussi un motif : lever une sanction est une décision
     * autant que la poser, et le journal doit pouvoir répondre « pourquoi ».
     */
    public function debloquer(User $client, string $motif): void
    {
        DB::transaction(function () use ($client, $motif) {
            $client->forceFill([
                'is_blocked' => false,
                'blocked_at' => null,
                'blocked_reason' => null,
            ])->save();

            AdminAction::log(AdminActionType::DEBLOCAGE_COMPTE, $client, $motif);
        });
    }

    /**
     * DEUX FILETS, et il en faut deux.
     *
     * Ici, on supprime les lignes de session du client — immédiat, mais cela
     * ne fonctionne qu'avec le pilote « database ». En fichier ou en cookie
     * chiffré, les sessions ne sont pas interrogeables par utilisateur.
     *
     * C'est pourquoi le middleware EnsureUserIsNotBlocked reste la garantie
     * réelle : quel que soit le pilote, un compte bloqué est éjecté à sa
     * requête suivante. Ce nettoyage-ci ne fait qu'avancer l'échéance.
     */
    private function detruireLesSessions(User $client): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = config('session.table', 'sessions');

        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->where('user_id', $client->id)->delete();
    }
}
