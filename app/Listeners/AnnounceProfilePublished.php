<?php

namespace App\Listeners;

use App\Enums\MotifAlerte;
use App\Events\ProfilePublished;
use App\Mail\ProfilePublishedMail;
use App\Services\AdminNotifier;
use App\Support\Courrier;

/**
 * La carte est en ligne : on envoie au client le lien qu'il va partager.
 *
 * C'est l'e-mail le plus UTILE du produit, et pour une raison très concrète :
 * il dépose l'adresse publique dans une boîte, donc sur le téléphone. De là,
 * elle se copie dans WhatsApp en deux gestes. Sans lui, il faudrait ouvrir un
 * navigateur, se connecter, retrouver l'écran — quatre occasions d'abandonner.
 */
class AnnounceProfilePublished
{
    public function __construct(private AdminNotifier $equipe) {}

    public function handle(ProfilePublished $event): void
    {
        $profile = $event->profile;
        $user = $profile->user;

        if (! $user) {
            return;   // profil orphelin : rien à qui écrire
        }

        $lien = route('profile.public', $profile->slug);

        Courrier::informer($user->email, new ProfilePublishedMail(
            name: $user->name,
            publicUrl: $lien,
            dashboardUrl: route('dashboard'),
            recipient: $user->email,
        ));

        $this->equipe->alerter(
            MotifAlerte::CarteActivee,
            [
                'Client' => $user->name,
                'Carte' => trim($profile->first_name.' '.$profile->last_name),
                'Adresse publique' => $lien,
            ],
            route('admin.profiles.index'),
        );
    }
}
