<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\MakeWebhook;

/**
 * Transmet le nouveau client à Make, pour l'invitation au groupe WhatsApp.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * À LA CONFIRMATION, JAMAIS À LA DEMANDE D'INSCRIPTION
 * ═══════════════════════════════════════════════════════════════════════
 * UserRegistered n'est émis qu'après le clic sur le lien de confirmation.
 * Une demande non confirmée n'est pas un client : c'est une adresse tapée
 * dans un formulaire, parfois par erreur, parfois par quelqu'un d'autre.
 *
 * Transmettre ces adresses-là à un service tiers avant que leur propriétaire
 * ait prouvé qu'elles lui appartiennent serait indéfendable — et le groupe
 * WhatsApp est réservé aux clients.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'ON TRANSMET, ET CE QU'ON NE TRANSMET PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Le nom, l'adresse, le téléphone : le minimum pour inviter quelqu'un et le
 * reconnaître. Rien d'autre ne sort.
 *
 * PAS L'IDENTIFIANT GOOGLE, qui est un secret d'authentification. PAS le
 * mot de passe, même haché. PAS l'adresse IP. Un service d'automatisation
 * n'a besoin d'aucun de ces éléments, et chacun d'eux serait une donnée de
 * plus à protéger chez un tiers.
 *
 * L'ORIGINE est transmise : elle permet au scénario de distinguer une
 * inscription par formulaire d'une inscription par Google, et de ne pas
 * envoyer deux fois le même message de bienvenue à quelqu'un qui en a déjà
 * reçu un.
 */
class CaptureProspect
{
    public function __construct(private MakeWebhook $make) {}

    public function handle(UserRegistered $event): void
    {
        if (! MakeWebhook::estConfigure()) {
            return;
        }

        $user = $event->user;

        /*
         | Aucun try/catch ici : MakeWebhook avale déjà toute panne et la
         | consigne. En ajouter un second donnerait l'illusion d'une seconde
         | protection, et masquerait le jour où la première disparaît.
         */
        $this->make->envoyer('inscription', [
            'id' => $user->id,
            'nom' => $user->name,
            'email' => $user->email,
            'telephone' => $user->phone,
            'origine' => $user->usesGoogle() ? 'google' : 'formulaire',
            'inscrit_le' => $user->created_at?->toIso8601String(),

            // Le lien du groupe voyage avec le prospect : le scénario Make
            // n'a ainsi pas à le stocker de son côté, et le changer chez nous
            // suffit à changer ce qui est envoyé.
            'groupe_whatsapp' => config('automation.whatsapp_groupe'),
        ]);
    }
}
