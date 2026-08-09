<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

/**
 * Seul le propriétaire accède à son profil. L'administrateur peut consulter
 * et suspendre, jamais modifier le contenu d'un client.
 */
class ProfilePolicy
{
    public function view(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id || $user->isAdmin();
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    public function delete(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id || $user->isAdmin();
    }

    /** Publier un profil suppose un abonnement actif. */
    public function publish(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id && $user->hasActiveSubscription();
    }

    /**
     * Télécharger le QR Code.
     *
     * Réservé au propriétaire, administrateur compris exclu : un QR est un
     * lien vers des coordonnées personnelles, il n'a pas à circuler depuis un
     * back-office.
     */
    public function downloadQr(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    /**
     * Télécharger la carte prête pour l'imprimeur.
     *
     * Exige EN PLUS que la carte soit publiée et l'abonnement valide. Sans
     * cela, on livrerait un fichier destiné à être imprimé en centaines
     * d'exemplaires alors que le lien qu'il porte ne répond pas encore —
     * des cartes physiques mortes à la sortie de l'imprimerie.
     */
    public function downloadPrintable(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id && $profile->isPubliclyVisible();
    }
}
