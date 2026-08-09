<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis une seule fois, au moment où le compte est réellement créé
 * (après confirmation de l'e-mail). C'est le point d'accroche de
 * l'ouverture de l'essai gratuit de 15 jours.
 */
class UserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}
