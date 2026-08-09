<?php

namespace App\Providers;

use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * Passerelle de paiement.
         *
         * Une seule implémentation existe : FakeGateway, qui déroule tout le
         * parcours sans appel réseau. Elle refuse elle-même de s'exécuter hors
         * développement — mais on ne compte pas sur ce seul garde-fou : le
         * jour où un opérateur est branché, c'est ici qu'on choisit lequel,
         * en un endroit et un seul.
         */
        $this->app->bind(PaymentGateway::class, function () {
            return match (config('services.payment.gateway', 'fake')) {
                default => new FakeGateway,
            };
        });
    }

    public function boot(): void
    {
        /*
         * PAGINATION — gabarit propre au produit.
         *
         * Ni Tailwind (le défaut de Laravel, dont les classes ne existent pas
         * ici) ni Bootstrap : les deux rendent une LISTE DE NUMÉROS. Sur 61
         * résultats elle paraît utile ; sur 3 000 elle devient
         * « 1 2 … 97 98 99 … 200 » et personne ne clique jamais sur 98.
         *
         * Le gabarit maison rend Précédent · Page N sur M · Suivant, plus un
         * champ de saut direct au-delà de deux pages.
         *
         * Il fait disparaître au passage le « Showing 16 to 30 of 61 results »
         * de Bootstrap : une phrase en anglais, dans une interface française,
         * qui doublait le compteur déjà affiché par chaque écran.
         */
        Paginator::defaultView('vendor.pagination.qrid');
        Paginator::defaultSimpleView('vendor.pagination.qrid');

        /*
         * $ctaUrl — destination de tous les appels à l'action des pages
         * publiques. Un visiteur va vers l'inscription ; un utilisateur déjà
         * connecté est renvoyé vers son espace, jamais vers un formulaire
         * d'inscription qu'il ne peut pas utiliser.
         *
         * Défini ici une seule fois : aucune page ne duplique cette logique.
         */
        View::composer(['welcome', 'landing.*', 'public.*', 'layouts.public'], function ($view) {
            $view->with('ctaUrl', auth()->check() ? route('dashboard') : route('register'));
        });
    }
}
