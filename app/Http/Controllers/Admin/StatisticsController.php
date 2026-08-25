<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Services\Admin\AdminStatsService;
use App\Services\StatistiquesLecture;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * STATISTIQUES — l'usage réel du produit.
 *
 * CET ÉCRAN N'A PAS DE MAQUETTE. Le menu validé porte l'entrée, les huit
 * maquettes fournies n'en montrent aucune.
 *
 * SA DIFFÉRENCE AVEC LA VUE D'ENSEMBLE est nette, sans quoi il ne mériterait
 * pas une entrée de menu : la vue d'ensemble répond à « où en est
 * l'entreprise » — comptes, abonnements, recettes. Celui-ci répond à « les
 * cartes servent-elles » — vues, scans, enregistrements, et quelles cartes.
 *
 * Un produit peut vendre beaucoup et n'être jamais utilisé. C'est précisément
 * ce que cet écran rend visible, et que le premier masquerait.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE LIT DES AGRÉGATS, PLUS LES ÉVÉNEMENTS BRUTS
 * ═══════════════════════════════════════════════════════════════════════
 * Cette page agrégeait `profile_events` en entier, trois fois. Mesuré sur
 * 1 000 profils :
 *
 *     2 000 événements  ......      45 ms
 *   100 000 événements  ......     450 ms
 * 1 000 000 événements  ......  32 301 ms
 *
 * Le classement à lui seul consommait 28,6 s. Tout passe désormais par
 * StatistiquesLecture, qui lit la table journalière — et la table source
 * pour la seule journée en cours, bornée par définition.
 *
 * COMPTE DES REQUÊTES — 7, dont aucune ne balaie l'historique complet.
 */
class StatisticsController extends Controller
{
    public function __construct(private readonly StatistiquesLecture $lecture) {}

    public function index(Request $request): View
    {
        $periode = AdminStatsService::periodeValide($request->query('periode'));
        $jours = AdminStatsService::PERIODES[$periode];
        $depuis = now()->subDays($jours);

        return view('admin.statistics', [
            'periode' => $periode,
            'periodes' => array_keys(AdminStatsService::PERIODES),
            'libellesPeriode' => OverviewController::LIBELLES_PERIODES,
            'totaux' => $this->lecture->totaux($depuis),
            'serie' => $this->lecture->serie($depuis, $jours),
            'classement' => $this->lecture->classement($depuis),
            'parModele' => $this->parModele(),
            'publication' => $this->publication(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $periode = AdminStatsService::periodeValide($request->query('periode'));
        $depuis = now()->subDays(AdminStatsService::PERIODES[$periode]);

        return CsvExport::stream(
            CsvExport::nom('statistiques-'.$periode),
            ['Profil', 'Identifiant public', 'Vues', 'Scans', 'Enregistrements', 'Total'],
            $this->lecture->classement($depuis, 500),
            fn ($ligne) => [
                $ligne->full_name,
                $ligne->slug,
                $ligne->vues,
                $ligne->scans,
                $ligne->saves,
                $ligne->total,
            ]
        );
    }

    /** Répartition des cartes publiées par modèle. */
    private function parModele()
    {
        return Profile::query()
            ->published()
            ->leftJoin('templates', 'templates.id', '=', 'profiles.template_id')
            ->groupBy('templates.id', 'templates.name')
            ->selectRaw('COALESCE(templates.name, ?) as nom, COUNT(*) as total', ['Sans modèle'])
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Publiés, brouillons, désactivés — en une requête.
     *
     * @return array<string, int>
     */
    private function publication(): array
    {
        $ligne = Profile::query()
            ->selectRaw('COUNT(*) as tous')
            ->selectRaw('SUM(is_active = 1) as publies')
            ->selectRaw('SUM(deactivated_at IS NOT NULL) as desactives')
            ->first();

        $tous = (int) ($ligne->tous ?? 0);
        $publies = (int) ($ligne->publies ?? 0);
        $desactives = (int) ($ligne->desactives ?? 0);

        return [
            'tous' => $tous,
            'publies' => $publies,
            'desactives' => $desactives,
            'brouillons' => max(0, $tous - $publies - $desactives),
        ];
    }
}
