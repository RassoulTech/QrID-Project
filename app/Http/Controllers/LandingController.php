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
        /*
         | LA MAQUETTE N'AFFICHE PLUS PERSONNE.
         |
         | Elle montrait le PREMIER PROFIL PUBLIÉ DE LA BASE, et le second dans
         | la section sombre. Sur la production, cela affichait sur la page
         | d'accueil publique le nom, la fonction, l'entreprise, le TÉLÉPHONE
         | et l'ADRESSE E-MAIL d'un client réel — à tout visiteur, sans qu'il
         | l'ait jamais demandé ni su.
         |
         | Le compteur de vues à côté du téléphone était pire encore : c'était
         | le nombre réel de consultations de ce compte. Une donnée
         | d'exploitation d'un client, publiée, et qui bougeait toute seule.
         |
         | Les deux maquettes viennent maintenant de `config/landing.php`.
         | Elles n'existent pas en base, ne sont enregistrées nulle part, et
         | ne changent que si on décide de les changer.
         |
         | Effet de bord bienvenu : l'accueil ne fait plus la requête de
         | profils ni son sous-comptage d'événements.
         */
        return view('welcome', [
            // Un visiteur va vers l'inscription ; un connecté vers son espace.
            'ctaUrl' => auth()->check() ? route('dashboard') : route('register'),

            'heroProfile' => $this->mockupProfile('mockup'),
            'showcaseProfile' => $this->mockupProfile('mockup_secondaire'),

            // Décoratif, pris en configuration. Voir le commentaire là-bas.
            'heroViews' => config('landing.mockup_vues'),

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
    private function mockupProfile(string $cle): Profile
    {
        return new Profile(config('landing.'.$cle));
    }
}
