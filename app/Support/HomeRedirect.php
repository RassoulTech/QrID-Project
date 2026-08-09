<?php

namespace App\Support;

use App\Models\User;

/**
 * Écran d'accueil d'un utilisateur, selon son rôle.
 *
 * Un seul endroit décide, pour que la connexion, la redirection des invités
 * et le middleware « déjà connecté » ne puissent jamais diverger entre elles.
 */
class HomeRedirect
{
    public static function for(?User $user): string
    {
        return $user?->isAdmin()
            ? route('admin.home')
            : route('dashboard');
    }
}
