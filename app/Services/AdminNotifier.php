<?php

namespace App\Services;

use App\Enums\MotifAlerte;
use App\Mail\AdminAlertMail;
use App\Support\Courrier;
use App\Support\DestinatairesEquipe;
use Illuminate\Support\Facades\Log;

/**
 * Prévenir l'équipe. Point d'entrée unique des six alertes du plan.
 *
 * Aucun listener ne construit lui-même la liste des destinataires : elle est
 * résolue ici, une fois. C'est ce qui permet de la changer — passer d'une
 * boîte partagée aux comptes en base, ou l'inverse — sans rouvrir six
 * fichiers, et de garantir qu'aucun motif n'utilise une liste différente des
 * autres.
 *
 * RIEN DE CE QUI SE PASSE ICI NE PEUT CASSER UN PARCOURS CLIENT. Les alertes
 * partent par Courrier, qui avale l'échec et le consigne. Une inscription
 * réussie ne doit pas devenir une erreur 500 parce que notre propre boîte est
 * indisponible — le client n'y est pour rien et n'y peut rien.
 */
class AdminNotifier
{
    /**
     * @param  array<string, string>  $lignes  couples libellé => valeur
     */
    public function alerter(MotifAlerte $motif, array $lignes, ?string $url = null): void
    {
        if (! $this->estActif($motif)) {
            return;
        }

        $destinataires = $this->destinataires();

        if ($destinataires === []) {
            /*
             | Aucun administrateur : l'alerte est perdue. C'est anormal et
             | cela doit laisser une trace — sinon l'équipe conclut que rien
             | ne se passe, alors que rien ne PART. La panne silencieuse est
             | exactement ce que ce bloc cherche à supprimer.
             */
            Log::channel('mail')->warning('Alerte sans destinataire', [
                'motif' => $motif->value,
            ]);

            return;
        }

        Courrier::informer($destinataires, new AdminAlertMail(
            motif: $motif,
            lignes: $lignes,
            url: $url,
            recipient: implode(', ', $destinataires),
        ));
    }

    /**
     * Les motifs d'ÉCHEC ne sont jamais désactivables.
     *
     * Un interrupteur sur « paiement en échec » finirait par être actionné un
     * jour de bruit, et personne ne le remettrait. Les alertes qu'on n'a pas
     * envie de lire sont celles qu'il faut protéger contre soi-même.
     */
    private function estActif(MotifAlerte $motif): bool
    {
        if ($motif->estUrgent()) {
            return true;
        }

        return (bool) config('notifications.admin_alerts.'.$motif->value, true);
    }

    /**
     * Résolue par DestinatairesEquipe, et non ici.
     *
     * Cette méthode et son homologue du formulaire de contact appliquaient la
     * même règle avec deux ordres de priorité différents. Deux implémentations
     * d'une même règle finissent toujours par diverger — et ici, la divergence
     * se serait constatée sur un message qui n'arrive pas.
     *
     * @return array<int, string>
     */
    private function destinataires(): array
    {
        return DestinatairesEquipe::alertes();
    }
}
