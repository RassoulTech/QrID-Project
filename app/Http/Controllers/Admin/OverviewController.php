<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Admin\AdminStatsService;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vue d'ensemble — le premier écran de l'administration.
 *
 * Le contrôleur ne calcule RIEN. Il lit la période demandée, la valide, et
 * demande le reste au service. Six agrégations écrites ici seraient six
 * requêtes intestables et impossibles à réutiliser dans un export.
 */
class OverviewController extends Controller
{
    /**
     * Libellés des six cartes, dans l'ordre d'affichage.
     *
     * Ils vivent ICI et pas dans le gabarit : l'export CSV doit produire
     * exactement les mêmes intitulés que l'écran, sinon on compare deux
     * documents qui ne parlent pas de la même chose.
     */
    public const LIBELLES_CARTES = [
        'utilisateurs' => 'Utilisateurs',
        'profils' => 'Profils',
        'abonnements_actifs' => 'Abonnements actifs',
        'chiffre_affaires' => 'Chiffre d\'affaires',
        'essais' => 'Essais en cours',
        'paiements_attente' => 'Paiements en attente',
    ];

    public const LIBELLES_PERIODES = [
        '7j' => '7 derniers jours',
        '30j' => '30 derniers jours',
        '90j' => '90 derniers jours',
        '12m' => '12 derniers mois',
    ];

    public function index(Request $request): View
    {
        $periode = AdminStatsService::periodeValide($request->query('periode'));
        $stats = new AdminStatsService($periode);

        return view('admin.overview', [
            'periode' => $periode,
            'periodes' => array_keys(AdminStatsService::PERIODES),
            'libellesPeriode' => self::LIBELLES_PERIODES,
            'libellesCartes' => self::LIBELLES_CARTES,
            'tonsPaiement' => [
                Payment::STATUS_SUCCESS => 'success',
                Payment::STATUS_PENDING => 'warning',
                Payment::STATUS_FAILED => 'danger',
            ],
            'libellesPaiement' => [
                Payment::STATUS_SUCCESS => 'Réussi',
                Payment::STATUS_PENDING => 'En attente',
                Payment::STATUS_FAILED => 'Échoué',
            ],
            'cartes' => $stats->cartes(),
            'tendance' => $stats->tendanceInscriptions(),
            'moyens' => $stats->moyensDePaiement(),
            'inscriptions' => $stats->dernieresInscriptions(),
            'paiements' => $stats->derniersPaiements(),
            'alerte' => $stats->alerte(),
        ]);
    }

    /**
     * Export du tableau de bord : les six chiffres et la tendance.
     *
     * Volontairement PAS les listes du bas — « dernières inscriptions » est un
     * extrait d'écran, pas un jeu de données. Qui veut les clients exporte
     * depuis la liste des clients, avec ses filtres.
     */
    public function export(Request $request): StreamedResponse
    {
        $periode = AdminStatsService::periodeValide($request->query('periode'));
        $stats = new AdminStatsService($periode);

        $lignes = [];

        foreach ($stats->cartes() as $cle => $carte) {
            $lignes[] = [
                'Indicateur',
                self::LIBELLES_CARTES[$cle] ?? $cle,
                $carte['valeur'],
                $carte['variation'] === null ? '' : $carte['variation'].' %',
            ];
        }

        foreach ($stats->tendanceInscriptions() as $point) {
            $lignes[] = ['Inscriptions', $point['libelle'], $point['valeur'], ''];
        }

        foreach ($stats->moyensDePaiement() as $moyen) {
            $lignes[] = ['Moyen de paiement', $moyen['libelle'], $moyen['total'], $moyen['part'].' %'];
        }

        return CsvExport::stream(
            CsvExport::nom('vue-ensemble-'.$periode),
            ['Bloc', 'Libellé', 'Valeur', 'Variation'],
            $lignes
        );
    }
}
