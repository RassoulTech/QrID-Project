<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Profile;
use Illuminate\View\View;

/**
 * Landing page. Toutes les données affichées viennent de la base ou de la
 * configuration : aucun contenu métier n'est écrit en dur dans les vues.
 */
class LandingController extends Controller
{
    public function index(): View
    {
        // Profils de démonstration (DemoSeeder) : le premier pour le hero,
        // le suivant pour la section sombre.
        $profiles = Profile::published()
            ->withCount(['events as views_count' => fn ($q) => $q->where('type', 'view')])
            ->orderBy('id')
            ->take(2)
            ->get();

        // La maquette du téléphone n'accepte pas d'absence de profil. Sans ce
        // repli, une base sans jeu de démonstration faisait tomber l'accueil
        // en erreur 500 — voir config/landing.php.
        $hero = $profiles->first() ?? $this->mockupProfile();

        return view('welcome', [
            // Un visiteur va vers l'inscription ; un connecté vers son espace.
            'ctaUrl' => auth()->check() ? route('dashboard') : route('register'),

            'heroProfile' => $hero,
            'showcaseProfile' => $profiles->get(1) ?? $hero,

            // Compteur réel, formaté « 1.2k » au-delà du millier.
            'heroViews' => $this->formatViews($hero?->views_count ?? 0),

            // Les tarifs viennent de la table plans, jamais du gabarit.
            'plans' => Plan::active()->orderBy('price_fcfa')->get(),

            'trades' => config('landing.trades'),
            'figures' => config('landing.figures'),
            'steps' => config('landing.steps'),
        ]);
    }

    /**
     * Profil d'illustration, JAMAIS enregistré en base.
     *
     * La relation socialLinks est laissée non chargée à dessein : x-phone
     * affiche alors ses pastilles décoratives sans jamais interroger la base
     * depuis la vue.
     */
    private function mockupProfile(): Profile
    {
        return new Profile(config('landing.mockup'));
    }

    private function formatViews(int $count): string
    {
        return $count >= 1000
            ? rtrim(rtrim(number_format($count / 1000, 1, '.', ''), '0'), '.').'k'
            : (string) $count;
    }
}
