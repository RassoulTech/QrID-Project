<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    /**
     * Profil public — la page que les contacts ouvrent après un scan.
     *
     * Un profil inactif n'est PAS visible : c'est la règle qui rend
     * l'abonnement nécessaire. Une 404 plutôt qu'un 403, pour ne rien
     * révéler de l'existence du profil.
     */
    public function show(string $slug): View
    {
        // isPubliclyVisible() consulte l'abonnement du propriétaire : sans ces
        // deux relations chargées d'avance, la page part en N+1 dès le premier
        // scan. C'est LA page qui prend tout le trafic.
        $profile = Profile::query()
            ->with(['socialLinks', 'user.subscriptions'])
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($profile->isPubliclyVisible(), 404);

        return view('public.profile', ['profile' => $profile]);
    }

    /**
     * Profil public de démonstration, ciblé par « Voir un exemple ».
     *
     * Prend le premier profil publié du jeu de démonstration. Si la base est
     * vide (production avant le premier client), la vue affiche un état vide
     * plutôt qu'une 404 au bout d'un lien de la page d'accueil.
     */
    public function demo(): View
    {
        $profile = Profile::published()
            ->with(['socialLinks', 'template'])
            ->oldest('id')
            ->first();

        return view('public.demo', ['profile' => $profile]);
    }
}
