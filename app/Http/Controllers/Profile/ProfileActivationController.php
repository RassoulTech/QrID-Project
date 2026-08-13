<?php

namespace App\Http\Controllers\Profile;

use App\Events\ProfilePublished;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Activation du profil depuis l'écran d'aperçu.
 *
 * RÈGLE MÉTIER : un profil n'est visible publiquement que si son propriétaire
 * a un abonnement actif. Pendant l'essai gratuit, cette condition est déjà
 * remplie — l'activation est donc immédiate et sans paiement.
 *
 * L'essai expiré, l'utilisateur est redirigé vers le paiement (étape suivante
 * du produit).
 */
class ProfileActivationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return redirect()->route('profile.create.step1');
        }

        $this->authorize('update', $profile);

        // Sans abonnement actif : passage par le paiement.
        if (! $request->user()->hasActiveSubscription()) {
            return redirect()->route('abonnement.paiement');
        }

        /*
         | ÉTAIT-ELLE DÉJÀ EN LIGNE ? La question décide de l'e-mail.
         |
         | Rien n'empêche de reposter ce formulaire — bouton recliqué, retour
         | arrière, double soumission. Sans cette lecture préalable, le client
         | recevrait « votre carte est en ligne » à chaque fois, et l'équipe
         | autant d'alertes pour un seul fait.
         */
        $etaitDejaEnLigne = (bool) $profile->is_active;

        $profile->forceFill(['is_active' => true])->save();

        if (! $etaitDejaEnLigne) {
            event(new ProfilePublished($profile));
        }

        return redirect()->route('dashboard')->with(
            'success',
            'Votre carte est active. Partagez-la dès maintenant.'
        );
    }
}
