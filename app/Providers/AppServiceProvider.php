<?php

namespace App\Providers;

use App\Database\ConnexionPostgres;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
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

        // Les octets des images partent en binaire vers PostgreSQL, et non
        // en texte. Voir App\Database\ConnexionPostgres.
        Connection::resolverFor('pgsql', fn ($pdo, $base, $prefixe, $config) => new ConnexionPostgres($pdo, $base, $prefixe, $config));
    }

    public function boot(): void
    {
        $this->surveillerLesRequetesLentes();
        $this->rechercherSansCasseNiAccent();
        $this->refuserLesIdentifiantsMalFormes();

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

    /**
     * UNE RECHERCHE TROUVE « Ndèye » QU'ON TAPE « ndeye » OU « NDEYE ».
     *
     * ═══════════════════════════════════════════════════════════════════
     * CE QUE MYSQL FAISAIT SANS LE DIRE
     * ═══════════════════════════════════════════════════════════════════
     * La collation utf8mb4_unicode_ci rend LIKE insensible à la casse ET aux
     * accents. Personne ne l'a jamais écrit : c'était gratuit.
     *
     * Sous PostgreSQL, LIKE compare octet à octet. « seck » ne trouve plus
     * « Seck », « elodie » ne trouve plus « Élodie » — et aucune erreur ne
     * le signale, la recherche répond simplement « aucun résultat ».
     *
     * whereLikeSouple rétablit le comportement d'origine : ILIKE pour la
     * casse, unaccent() des deux côtés pour les accents (extension créée par
     * la migration create_unaccent_extension). Sur MySQL et SQLite, c'est le
     * LIKE d'avant, à l'identique.
     */
    private function rechercherSansCasseNiAccent(): void
    {
        QueryBuilder::macro('whereLikeSouple', function (string $colonne, string $motif, string $boolean = 'and') {
            /** @var QueryBuilder $this */
            if ($this->getConnection()->getDriverName() !== 'pgsql') {
                return $this->where($colonne, 'like', $motif, $boolean);
            }

            $colonne = $this->getGrammar()->wrap($colonne);

            return $this->whereRaw("unaccent({$colonne}) ilike unaccent(?)", [$motif], $boolean);
        });

        QueryBuilder::macro('orWhereLikeSouple', function (string $colonne, string $motif) {
            /** @var QueryBuilder $this */
            return $this->whereLikeSouple($colonne, $motif, 'or');
        });
    }

    /**
     * UNE ADRESSE MAL FORMÉE DONNE 404, PAS 500.
     *
     * MySQL compare « /admin/clients/abc » à un identifiant numérique, ne
     * trouve rien, et Laravel répond 404. PostgreSQL refuse la comparaison
     * elle-même — « invalid input syntax for type bigint » — et la page
     * tombe en erreur serveur, avec une alerte à la clé.
     *
     * On filtre donc au routage, avant toute requête : ces trois paramètres
     * sont des identifiants numériques. Les profils, modèles et formules se
     * résolvent par leur slug, une chaîne : ils ne sont pas concernés.
     */
    private function refuserLesIdentifiantsMalFormes(): void
    {
        Route::pattern('user', '[0-9]+');
        Route::pattern('payment', '[0-9]+');
        Route::pattern('notification', '[0-9]+');
    }
}
