<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\ProfileEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * « Statistiques » — les chiffres réels, agrégés en SQL.
 *
 * Trois requêtes bornées, quel que soit le volume d'événements : les totaux,
 * la série par jour, et les derniers événements. Aucune boucle de requêtes,
 * aucun comptage en PHP sur des milliers de lignes.
 *
 * Sans aucune donnée, on n'affiche PAS un graphique vide : on explique quoi
 * faire pour qu'il se remplisse.
 */
class StatisticsController extends Controller
{
    private const PERIODES = [7, 30, 90];

    public function __invoke(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return redirect()->route('profile.create.step1')
                ->with('info', 'Créez d\'abord votre carte : ses statistiques suivront.');
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
     * Totaux de la période, en UNE requête à agrégats conditionnels.
     *
     * @return array{views:int, scans:int, saves:int, total:int}
     */
    private function totaux(int $profileId, int $jours): array
    {
        $ligne = ProfileEvent::query()
            ->where('profile_id', $profileId)
            ->where('created_at', '>=', now()->startOfDay()->subDays($jours - 1))
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(type = ?) as vues', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(type = ?) as scans', [ProfileEvent::TYPE_SCAN])
            ->selectRaw('SUM(type = ?) as saves', [ProfileEvent::TYPE_SAVE])
            ->first();

        return [
            'views' => (int) ($ligne?->vues ?? 0),
            'scans' => (int) ($ligne?->scans ?? 0),
            'saves' => (int) ($ligne?->saves ?? 0),
            'total' => (int) ($ligne?->total ?? 0),
        ];
    }

    /**
     * Série journalière, vues et scans séparés.
     *
     * Le groupBy SQL ne renvoie que les jours ayant un événement : on repart
     * d'une série pleine et on y verse les comptes, sinon les barres se
     * tasseraient et le graphique mentirait sur les creux.
     *
     * @return list<array{jour:string, libelle:string, vues:int, scans:int}>|null
     */
    private function serie(int $profileId, int $jours): ?array
    {
        $depuis = now()->startOfDay()->subDays($jours - 1);

        $lignes = ProfileEvent::query()
            ->where('profile_id', $profileId)
            ->where('created_at', '>=', $depuis)
            ->selectRaw('DATE(created_at) as jour')
            ->selectRaw('SUM(type = ?) as vues', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(type = ?) as scans', [ProfileEvent::TYPE_SCAN])
            ->groupBy('jour')
            ->get()
            ->keyBy('jour');

        if ($lignes->isEmpty()) {
            return null;
        }

        $serie = [];

        for ($i = 0; $i < $jours; $i++) {
            $date = $depuis->copy()->addDays($i);
            $cle = $date->toDateString();
            $ligne = $lignes->get($cle);

            $serie[] = [
                'jour' => $cle,
                'libelle' => $date->translatedFormat($jours <= 7 ? 'D' : 'j/m'),
                'vues' => (int) ($ligne?->vues ?? 0),
                'scans' => (int) ($ligne?->scans ?? 0),
            ];
        }

        return $serie;
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
