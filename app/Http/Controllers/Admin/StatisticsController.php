<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Services\Admin\AdminStatsService;
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
 * COMPTE DES REQUÊTES — 6 : trois compteurs d'événements en un balayage,
 * la série journalière, le classement des profils, la répartition par modèle,
 * les totaux de publication.
 */
class StatisticsController extends Controller
{
    public function index(Request $request): View
    {
        $periode = AdminStatsService::periodeValide($request->query('periode'));
        $jours = AdminStatsService::PERIODES[$periode];
        $depuis = now()->subDays($jours);

        return view('admin.statistics', [
            'periode' => $periode,
            'periodes' => array_keys(AdminStatsService::PERIODES),
            'libellesPeriode' => OverviewController::LIBELLES_PERIODES,
            'totaux' => $this->totaux($depuis),
            'serie' => $this->serie($depuis, $jours),
            'classement' => $this->classement($depuis),
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
            $this->classement($depuis, 500),
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

    /**
     * Les trois compteurs EN UNE SEULE REQUÊTE.
     *
     * `SUM(condition)` rend 1 ou 0 sur MySQL comme sur SQLite. Trois `count()`
     * séparés coûteraient trois balayages de la même table pour trois
     * photographies prises à trois instants différents.
     *
     * @return array<string, int>
     */
    private function totaux($depuis): array
    {
        $ligne = ProfileEvent::query()
            ->where('created_at', '>=', $depuis)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(type = ?) as vues', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(type = ?) as scans', [ProfileEvent::TYPE_SCAN])
            ->selectRaw('SUM(type = ?) as saves', [ProfileEvent::TYPE_SAVE])
            ->first();

        return [
            'total' => (int) ($ligne->total ?? 0),
            'vues' => (int) ($ligne->vues ?? 0),
            'scans' => (int) ($ligne->scans ?? 0),
            'saves' => (int) ($ligne->saves ?? 0),
        ];
    }

    /**
     * Série journalière, TROUS COMBLÉS À ZÉRO.
     *
     * Un GROUP BY ne rend que les jours peuplés. Sans remplissage, une semaine
     * sans aucun scan se dessinerait comme une simple barre manquante — ce que
     * l'œil lit comme un jour serré, et non comme un arrêt.
     */
    private function serie($depuis, int $jours)
    {
        $brut = ProfileEvent::query()
            ->where('created_at', '>=', $depuis)
            ->selectRaw('DATE(created_at) as jour, COUNT(*) as total')
            ->groupBy('jour')
            ->pluck('total', 'jour');

        // Au-delà de 60 jours, une barre par jour devient illisible : on ne
        // garde que les 60 derniers points, les plus utiles.
        $points = collect();
        $depart = max(1, min($jours, 60));

        for ($i = $depart - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $points->push([
                'libelle' => $date->translatedFormat('d/m'),
                'valeur' => (int) ($brut[$date->format('Y-m-d')] ?? 0),
            ]);
        }

        return $points;
    }

    /**
     * Les profils les plus consultés.
     *
     * Trois sous-agrégats dans UNE jointure, pas un comptage par profil : la
     * seconde forme donnerait une requête par ligne du classement.
     */
    private function classement($depuis, int $limite = 10)
    {
        return Profile::query()
            ->join('profile_events', 'profile_events.profile_id', '=', 'profiles.id')
            ->where('profile_events.created_at', '>=', $depuis)
            ->groupBy('profiles.id', 'profiles.first_name', 'profiles.last_name', 'profiles.slug')
            ->selectRaw('profiles.id, profiles.first_name, profiles.last_name, profiles.slug')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(profile_events.type = ?) as vues', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(profile_events.type = ?) as scans', [ProfileEvent::TYPE_SCAN])
            ->selectRaw('SUM(profile_events.type = ?) as saves', [ProfileEvent::TYPE_SAVE])
            ->orderByDesc('total')
            ->limit($limite)
            ->get();
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
