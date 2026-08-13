<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Mail\PasswordChangedMail;
use App\Support\Courrier;

/**
 * Alerte de sécurité au titulaire du compte.
 *
 * Elle part par Courrier, donc sans jamais interrompre la requête : un
 * changement de mot de passe qui A RÉUSSI ne doit pas se terminer par une
 * erreur sous prétexte que la confirmation n'est pas partie. L'utilisateur
 * serait alors persuadé que son nouveau mot de passe n'a pas été pris, et
 * essaierait l'ancien.
 *
 * L'échec reste consigné dans mail_logs : sur un e-mail de sécurité, savoir
 * qu'il n'est pas parti a une valeur propre.
 */
class AnnouncePasswordChanged
{
    public function handle(PasswordChanged $event): void
    {
        $user = $event->user;

        Courrier::informer($user->email, new PasswordChangedMail(
            name: $user->name,
            date: now()->translatedFormat('j F Y à H:i'),
            ip: $event->ip,
            resetUrl: route('password.request'),
            recipient: $user->email,
        ));
    }
}
