<?php

namespace App\Providers;

use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $this->surveillerLesRequetesLentes();

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

    /**
     * TOUTE REQUÊTE LENTE EST JOURNALISÉE, AVEC CE QUI L'A DÉCLENCHÉE.
     *
     * ═══════════════════════════════════════════════════════════════════
     * SANS MESURE, AUCUNE DÉCISION DE MONTÉE EN CHARGE N'EST POSSIBLE
     * ═══════════════════════════════════════════════════════════════════
     * L'audit a trouvé une requête à 28,6 secondes. Elle existait depuis des
     * mois. Rien, à aucun moment, ne l'avait signalée — parce que rien ne
     * regardait.
     *
     * Le seuil vaut le budget d'une page entière : 300 ms. Une SEULE requête
     * qui le consomme est déjà une anomalie, même si la page répond encore.
     *
     * ON JOURNALISE L'ADRESSE, PAS SEULEMENT LE SQL. Une requête lente sans
     * son contexte oblige à la chercher dans le code ; avec la route, on sait
     * immédiatement quel écran la déclenche et qui l'a ouvert.
     *
     * Le seuil à 0 désactive la surveillance — utile pendant les tests, où
     * chaque requête paie le prix d'une base fraîchement migrée.
     */
    private function surveillerLesRequetesLentes(): void
    {
        $seuil = (int) config('statistiques.seuil_requete_lente_ms', 300);

        if ($seuil <= 0 || app()->runningUnitTests()) {
            return;
        }

        DB::listen(function (QueryExecuted $requete) use ($seuil) {
            if ($requete->time < $seuil) {
                return;
            }

            Log::warning('Requête lente', [
                'ms' => round($requete->time, 1),
                'sql' => Str::limit(preg_replace('/\s+/', ' ', $requete->sql), 300),
                'route' => request()->path(),
            ]);
        });
    }
}
