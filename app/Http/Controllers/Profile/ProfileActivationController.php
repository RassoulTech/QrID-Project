<?php

namespace App\Http\Controllers\Profile;

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

        $profile->forceFill(['is_active' => true])->save();

        return redirect()->route('dashboard')->with(
            'success',
            'Votre carte est active. Partagez-la dès maintenant.'
        );
    }
}
