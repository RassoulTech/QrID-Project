<?php

namespace App\Services;

use App\Models\ProfileEvent;
use App\Models\ProfileStatDaily;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * LA LECTURE DES STATISTIQUES — agrégats pour l'histoire, source pour le jour.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI DEUX SOURCES, ET NON UNE
 * ═══════════════════════════════════════════════════════════════════════
 * L'agrégation tourne la nuit et ne traite que les journées TERMINÉES : le
 * jour en cours n'y figure pas. Ne lire que les agrégats afficherait donc
 * une page qui ignore ce qui s'est passé depuis minuit — et le premier
 * client qui scanne sa propre carte pour vérifier verrait zéro.
 *
 * Ne lire que la source, à l'inverse, est exactement le problème qu'on
 * vient de corriger : 32 secondes sur un million d'événements.
 *
 * On lit donc les DEUX, et on additionne :
 *
 *   historique  →  profile_stats_daily, une ligne par profil et par jour
 *   aujourd'hui →  profile_events, borné à la journée en cours
 *
 * La seconde requête ne touche que quelques centaines de lignes, quel que
 * soit l'âge du produit. C'est la borne qui rend l'ensemble prévisible.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * MESURES QUI JUSTIFIENT CE SERVICE
 * ═══════════════════════════════════════════════════════════════════════
 * Page de statistiques de l'administration, sur 1 000 profils :
 *
 *     2 000 événements  ......      45 ms
 *   100 000 événements  ......     450 ms
 * 1 000 000 événements  ......  32 301 ms
 *
 * Le classement des profils à lui seul consommait 28,6 s : EXPLAIN montrait
 * « Using temporary; Using filesort » sur la totalité de l'historique, pour
 * n'en garder que dix lignes.
 */
class StatistiquesLecture
{
    /**
     * Les quatre compteurs d'une période, tous profils confondus.
     *
     * @return array{total:int, vues:int, scans:int, saves:int}
     */
    public function totaux(Carbon $depuis, ?int $profileId = null): array
    {
        $agregats = ProfileStatDaily::query()
            ->where('jour', '>=', $depuis->toDateString())
            ->where('jour', '<', Carbon::today()->toDateString())
            ->when($profileId, fn ($q) => $q->where('profile_id', $profileId))
            ->selectRaw('COALESCE(SUM(vues),0) v, COALESCE(SUM(scans),0) s, COALESCE(SUM(saves),0) e, COALESCE(SUM(total),0) t')
            ->first();

        $aujourdhui = $this->totauxDuJour($profileId);

        return [
            'vues' => (int) $agregats->v + $aujourdhui['vues'],
            'scans' => (int) $agregats->s + $aujourdhui['scans'],
            'saves' => (int) $agregats->e + $aujourdhui['saves'],
            'total' => (int) $agregats->t + $aujourdhui['total'],
        ];
    }

    /** @return array{total:int, vues:int, scans:int, saves:int} */
    private function totauxDuJour(?int $profileId = null): array
    {
        $ligne = ProfileEvent::query()
            ->where('created_at', '>=', Carbon::today())
            ->when($profileId, fn ($q) => $q->where('profile_id', $profileId))
            ->selectRaw('COUNT(*) t')
            ->selectRaw('SUM(type = ?) v', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(type = ?) s', [ProfileEvent::TYPE_SCAN])
            ->selectRaw('SUM(type = ?) e', [ProfileEvent::TYPE_SAVE])
            ->first();

        return [
            'total' => (int) ($ligne->t ?? 0),
            'vues' => (int) ($ligne->v ?? 0),
            'scans' => (int) ($ligne->s ?? 0),
            'saves' => (int) ($ligne->e ?? 0),
        ];
    }

    /**
     * La série journalière — un point par jour, trous comblés à zéro.
     *
     * LE GROUPEMENT PORTE SUR UNE COLONNE DATE, plus sur DATE(created_at).
     * Une fonction appliquée à une colonne interdit tout index : c'est ce
     * qui coûtait 1,9 s sur un million de lignes, en filesort complet. Ici
     * la colonne `jour` est indexée et le groupement s'y appuie.
     *
     * @return Collection<string, int>
     */
    public function serie(Carbon $depuis, int $jours, ?int $profileId = null): Collection
    {
        $brut = ProfileStatDaily::query()
            ->where('jour', '>=', $depuis->toDateString())
            ->when($profileId, fn ($q) => $q->where('profile_id', $profileId))
            ->groupBy('jour')
            ->selectRaw('jour, SUM(total) as total')
            ->pluck('total', 'jour')
            ->mapWithKeys(fn ($total, $jour) => [Carbon::parse($jour)->toDateString() => (int) $total]);

        // Le jour en cours vient de la source : il n'est pas encore agrégé.
        $brut[Carbon::today()->toDateString()] = $this->totauxDuJour($profileId)['total'];

        // Au-delà de 60 jours, une barre par jour devient illisible : on ne
        // garde que les 60 derniers points, les plus utiles.
        $points = collect();
        $depart = max(1, min($jours, 60));

        for ($i = $depart - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $points[$date->toDateString()] = (int) ($brut[$date->toDateString()] ?? 0);
        }

        return $points;
    }

    /**
     * Les profils les plus consultés sur la période.
     *
     * ═══════════════════════════════════════════════════════════════════
     * C'EST LA REQUÊTE QUI COÛTAIT 28,6 SECONDES
     * ═══════════════════════════════════════════════════════════════════
     * Elle joignait `profiles` à `profile_events` et agrégeait tout
     * l'historique de chaque profil pour n'en garder que dix lignes.
     *
     * Elle lit désormais les agrégats — mille fois moins de lignes — et
     * ne joint `profiles` qu'APRÈS avoir réduit à la poignée de profils
     * qui nous intéressent. C'est l'ordre qui fait la différence : joindre
     * d'abord, c'est trier des millions de lignes pour en jeter la
     * quasi-totalité.
     */
    public function classement(Carbon $depuis, int $limite = 10): Collection
    {
        $tete = ProfileStatDaily::query()
            ->where('jour', '>=', $depuis->toDateString())
            ->groupBy('profile_id')
            ->selectRaw('profile_id')
            ->selectRaw('SUM(vues) as vues, SUM(scans) as scans, SUM(saves) as saves, SUM(total) as total')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        if ($tete->isEmpty()) {
            return collect();
        }

        // Une seule requête pour les noms, sur une poignée d'identifiants.
        $profils = DB::table('profiles')
            ->whereIn('id', $tete->pluck('profile_id'))
            ->get(['id', 'first_name', 'last_name', 'slug'])
            ->keyBy('id');

        return $tete->map(function ($ligne) use ($profils) {
            $p = $profils[$ligne->profile_id] ?? null;

            /*
             | first_name ET last_name SONT RENDUS SÉPARÉMENT, en plus du nom
             | complet. La vue les compose elle-même — elle le faisait déjà
             | avant ce service, et ne rien lui retirer évite de la modifier
             | pour un changement qui ne la concerne pas.
             */
            return (object) [
                'id' => $ligne->profile_id,
                'first_name' => $p->first_name ?? '',
                'last_name' => $p->last_name ?? '',
                'full_name' => $p ? trim($p->first_name.' '.$p->last_name) : '—',
                'slug' => $p->slug ?? null,
                'vues' => (int) $ligne->vues,
                'scans' => (int) $ligne->scans,
                'saves' => (int) $ligne->saves,
                'total' => (int) $ligne->total,
            ];
        });
    }
}
