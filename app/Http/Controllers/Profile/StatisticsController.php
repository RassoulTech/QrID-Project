<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\ProfileEvent;
use App\Services\StatistiquesLecture;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * « Statistiques » — les chiffres réels, agrégés en SQL.
 *
 * Trois lectures bornées, quel que soit le volume d'événements : les totaux,
 * la série par jour, et les derniers événements. Aucune boucle de requêtes,
 * aucun comptage en PHP sur des milliers de lignes.
 *
 * Sans aucune donnée, on n'affiche PAS un graphique vide : on explique quoi
 * faire pour qu'il se remplisse.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES CHIFFRES VIENNENT DU SERVICE, PLUS DE LA TABLE BRUTE
 * ═══════════════════════════════════════════════════════════════════════
 * Cet écran interrogeait `profile_events` directement, en groupant sur
 * `DATE(created_at)`. Une fonction appliquée à une colonne interdit tout
 * index : c'est la requête que `StatistiquesLecture` a été écrite pour
 * supprimer, mesurée à 1,9 s sur un million de lignes. Le correctif avait
 * été appliqué à l'administration, et à elle seule.
 *
 * Il ne reste ici que ce qui relève de la PRÉSENTATION — le libellé d'un
 * axe, le choix d'un état vide. Les chiffres eux-mêmes ont une seule
 * source, partagée avec l'administration.
 */
class StatisticsController extends Controller
{
    private const PERIODES = [7, 30, 90];

    public function __construct(private readonly StatistiquesLecture $lecture) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return redirect()->route('profile.create.step1')
                ->with('info', __('profile.flash.carte_avant_stats'));
        }

        $jours = (int) $request->query('periode', 30);
        $jours = in_array($jours, self::PERIODES, true) ? $jours : 30;

        return view('statistiques.index', [
            'profile' => $profile,
            'periode' => $jours,
            'periodes' => self::PERIODES,
            'totaux' => $this->totaux($profile->id, $jours),
            'serie' => $this->serie($profile->id, $jours),
            'derniers' => $this->derniers($profile->id),
        ]);
    }

    /**
     * Totaux de la période — agrégats pour l'histoire, source pour le jour.
     *
     * `views` et non `vues` : la vue lit cette clé-là depuis toujours, et la
     * renommer obligerait à toucher un gabarit pour un changement qui ne le
     * concerne pas.
     *
     * @return array{views:int, scans:int, saves:int, total:int}
     */
    private function totaux(int $profileId, int $jours): array
    {
        $totaux = $this->lecture->totaux($this->depuis($jours), $profileId);

        return [
            'views' => $totaux['vues'],
            'scans' => $totaux['scans'],
            'saves' => $totaux['saves'],
            'total' => $totaux['total'],
        ];
    }

    /**
     * Série journalière, vues et scans séparés.
     *
     * Le service rend une série PLEINE — un point par jour, trous comblés à
     * zéro. Sans cela les barres se tasseraient et le graphique mentirait
     * sur les creux.
     *
     * On rend `null`, et non une série de zéros, quand rien n'a été
     * enregistré : la vue affiche alors quoi faire pour la remplir, ce qui
     * vaut mieux qu'un graphique plat sans explication.
     *
     * @return list<array{jour:string, libelle:string, vues:int, scans:int}>|null
     */
    private function serie(int $profileId, int $jours): ?array
    {
        $points = $this->lecture->serieDetaillee($this->depuis($jours), $jours, $profileId);

        if ($points->every(fn (array $jour) => $jour['total'] === 0)) {
            return null;
        }

        return $points->map(fn (array $jour, string $date) => [
            'jour' => $date,
            // Le libellé de l'axe est de la présentation : il reste ici.
            'libelle' => Carbon::parse($date)->translatedFormat($jours <= 7 ? 'D' : 'j/m'),
            'vues' => $jour['vues'],
            'scans' => $jour['scans'],
        ])->values()->all();
    }

    /**
     * Le premier jour de la fenêtre.
     *
     * `$jours - 1` parce que la fenêtre INCLUT aujourd'hui : sur sept jours,
     * on remonte de six.
     */
    private function depuis(int $jours): Carbon
    {
        return Carbon::today()->subDays($jours - 1);
    }

    /** @return Collection<int, ProfileEvent> */
    private function derniers(int $profileId)
    {
        return ProfileEvent::query()
            ->where('profile_id', $profileId)
            ->latest('created_at')
            ->limit(15)
            ->get(['id', 'type', 'created_at']);
    }
}
