<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Le mot de passe vient d'être modifié. E-mail de SÉCURITÉ, pas de confort.
 *
 * Il part quel que soit le chemin emprunté — réinitialisation par lien, ou
 * changement depuis les paramètres du compte. Sa raison d'être : si ce n'est
 * pas le titulaire qui a agi, ce message est le seul signal qu'il recevra
 * avant de perdre l'accès à son compte. Il ne doit jamais être désactivable.
 *
 * L'adresse IP est indicative et volontairement non géolocalisée : derrière
 * un opérateur mobile sénégalais, une ville affichée serait fausse une fois
 * sur deux et détournerait l'attention du seul fait qui compte — « ce n'est
 * pas moi ».
 */
class PasswordChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $ip = null,
    ) {}
}
