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
 * On lit donc les DEUX, séparés par une FRONTIÈRE MOBILE :
 *
 *   avant la frontière  →  profile_stats_daily, un compteur par jour
 *   après la frontière  →  profile_events, la source
 *
 * La frontière n'est pas « minuit » mais LE LENDEMAIN DU DERNIER JOUR
 * RÉELLEMENT AGRÉGÉ. La nuance est tout le sujet : l'agrégation est une
 * tâche planifiée, et une tâche planifiée peut ne pas tourner. Placer la
 * frontière à minuit reviendrait à parier qu'elle a tourné — et le jour où
 * elle ne tourne pas, l'historique du client disparaît de son écran sans
 * qu'aucune erreur ne le signale.
 *
 * En régime normal la portion lue dans la source vaut UN JOUR. Si la tâche
 * a manqué N nuits, elle vaut N+1 jours : le service ralentit, il ne ment
 * pas. Et parce que la frontière est unique, aucune journée n'est lue dans
 * les deux tables — un jour agrégé dont les événements bruts n'ont pas
 * encore été purgés n'est jamais compté deux fois.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * UNE SEULE SÉRIE, TOUT LE RESTE EN DÉCOULE
 * ═══════════════════════════════════════════════════════════════════════
 * `serieDetaillee()` est le seul chemin de lecture. `serie()` en est une
 * projection, `totaux()` en est la somme, et les trois écrans qui affichent
 * ces chiffres — tableau de bord, statistiques du client, administration —
 * passent tous par ici. Chacun entretenait auparavant sa propre requête,
 * groupée sur `DATE(created_at)` : trois définitions du même mot, dont deux
 * qu'on oubliait de corriger.
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
     * Les compteurs d'une période, tous profils confondus.
     *
     * `total` NE COMPREND PAS LES PARTAGES : il vaut vues + scans +
     * enregistrements, exactement ce qu'il valait avant leur arrivée. Voir
     * la note dans AgregerStatistiques.
     *
     * @return array{total:int, vues:int, scans:int, saves:int, partages:int}
     */
    public function totaux(Carbon $depuis, ?int $profileId = null): array
    {
        /*
         | LES TOTAUX SONT LA SOMME DE LA SÉRIE, et non une requête de plus.
         |
         | Deux requêtes qui comptent la même chose finissent par ne plus
         | dire la même chose — l'une est corrigée, l'autre est oubliée.
         | Sommer une série de quatre-vingt-dix entiers en PHP ne coûte rien
         | de mesurable ; en revanche, garder deux définitions du mot
         | « total » coûte un jour de recherche le jour où elles divergent.
         */
        $jours = (int) $depuis->copy()->startOfDay()->diffInDays(Carbon::today()) + 1;

        $serie = $this->serieDetaillee($depuis, $jours, $profileId);

        return [
            'vues' => (int) $serie->sum('vues'),
            'scans' => (int) $serie->sum('scans'),
            'saves' => (int) $serie->sum('saves'),
            'partages' => (int) $serie->sum('partages'),
            'total' => (int) $serie->sum('total'),
        ];
    }

    /**
     * LES TOTAUX DEPUIS TOUJOURS — sans série intermédiaire.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CETTE MÉTHODE EXISTE À CÔTÉ DE `totaux()`
     * ═══════════════════════════════════════════════════════════════════
     * Le tableau de bord affiche des compteurs CUMULÉS : « 342 vues », pas
     * « 342 vues sur trente jours ». Il les calculait par un SUM sur toute
     * la table d'événements du profil, sans aucune borne de date.
     *
     * Sur un profil ouvert la semaine dernière, c'est instantané. Sur un
     * profil très consulté depuis deux ans, c'est un balayage qui grossit
     * indéfiniment — et c'est la page la plus ouverte de l'espace client.
     *
     * Passer par `totaux()` aurait marché, mais construirait une série d'un
     * point PAR JOUR depuis la création du profil : sept cents entiers en
     * mémoire pour n'en additionner que quatre. On somme donc directement.
     *
     * La frontière reste la même que partout ailleurs : agrégats jusqu'au
     * dernier jour traité, source au-delà.
     *
     * @return array{total:int, vues:int, scans:int, saves:int, partages:int}
     */
    public function totauxCumules(?int $profileId = null): array
    {
        $dernierAgrege = $this->dernierJourAgrege();

        $debutSource = $dernierAgrege === null
            ? null                                        // rien n'a jamais été agrégé
            : Carbon::parse($dernierAgrege)->addDay();

        $agregats = ProfileStatDaily::query()
            ->when($profileId, fn ($q) => $q->where('profile_id', $profileId))
            ->when($debutSource, fn ($q) => $q->where('jour', '<', $debutSource->toDateString()))
            ->selectRaw('COALESCE(SUM(vues),0) v, COALESCE(SUM(scans),0) s')
            ->selectRaw('COALESCE(SUM(saves),0) e, COALESCE(SUM(partages),0) p, COALESCE(SUM(total),0) t')
            ->first();

        $source = ProfileEvent::query()
            ->when($profileId, fn ($q) => $q->where('profile_id', $profileId))
            ->when($debutSource, fn ($q) => $q->where('created_at', '>=', $debutSource->startOfDay()))
            ->selectRaw('SUM(type IN (?, ?, ?)) t', [
                ProfileEvent::TYPE_VIEW, ProfileEvent::TYPE_SCAN, ProfileEvent::TYPE_SAVE,
            ])
            ->selectRaw('SUM(type = ?) v', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(type = ?) s', [ProfileEvent::TYPE_SCAN])
            ->selectRaw('SUM(type = ?) e', [ProfileEvent::TYPE_SAVE])
            ->selectRaw('SUM(type = ?) p', [ProfileEvent::TYPE_SHARE])
            ->first();

        return [
            'vues' => (int) $agregats->v + (int) ($source->v ?? 0),
            'scans' => (int) $agregats->s + (int) ($source->s ?? 0),
            'saves' => (int) $agregats->e + (int) ($source->e ?? 0),
            'partages' => (int) $agregats->p + (int) ($source->p ?? 0),
            'total' => (int) $agregats->t + (int) ($source->t ?? 0),
        ];
    }

    /**
     * JUSQU'OÙ L'AGRÉGATION A-T-ELLE RÉELLEMENT TOURNÉ ?
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CETTE QUESTION EST POSÉE À CHAQUE LECTURE
     * ═══════════════════════════════════════════════════════════════════
     * L'agrégation est une tâche planifiée. Une tâche planifiée peut ne pas
     * tourner : le planificateur n'existe pas encore en production, et le
     * jour où il existera il pourra tomber en panne une nuit sans que
     * personne le remarque.
     *
     * Si l'on se contentait de lire les agrégats, cette panne se
     * traduirait par un historique qui DISPARAÎT de l'écran du client,
     * sans erreur, sans trace, sans que rien ne l'annonce. L'exactitude
     * d'un chiffre affiché ne doit pas dépendre du bon fonctionnement
     * silencieux d'un processus d'exploitation.
     *
     * On mesure donc où l'agrégation s'est arrêtée, et l'on relit la
     * SOURCE pour tout ce qui vient après. Le service reste rapide quand
     * la tâche tourne — un seul jour à lire — et reste JUSTE quand elle ne
     * tourne pas, au prix de la lenteur d'avant. Se dégrader en lenteur
     * est acceptable ; se dégrader en mensonge ne l'est pas.
     *
     * La borne est GLOBALE et non par profil, à dessein : un profil sans
     * la moindre visite un mardi n'a pas de ligne d'agrégat ce mardi-là.
     * L'absence de ligne ne dit donc rien sur l'agrégation ; seule la date
     * la plus avancée de la table le dit.
     */
    private function dernierJourAgrege(): ?string
    {
        $max = ProfileStatDaily::query()->max('jour');

        return $max ? Carbon::parse($max)->toDateString() : null;
    }

    /**
     * Les journées lues DANS LA SOURCE, depuis `$depuis` et jusqu'à ce jour.
     *
     * C'est la requête coûteuse — celle qui groupe sur `DATE(created_at)` et
     * ne peut donc s'appuyer sur aucun index. Elle n'est appelée que sur la
     * portion NON AGRÉGÉE de la fenêtre : un seul jour en régime normal.
     *
     * @return Collection<string, array{vues:int, scans:int, saves:int, partages:int, total:int}>
     */
    private function depuisLaSource(Carbon $depuis, ?int $profileId = null): Collection
    {
        return ProfileEvent::query()
            ->where('created_at', '>=', $depuis->copy()->startOfDay())
            ->when($profileId, fn ($q) => $q->where('profile_id', $profileId))
            ->selectRaw('DATE(created_at) jour')
            // `t` compte les trois types historiques, jamais les partages :
            // voir la note dans AgregerStatistiques. Un total qui change de
            // définition est un total auquel on ne peut plus se fier.
            ->selectRaw('SUM(type IN (?, ?, ?)) t', [
                ProfileEvent::TYPE_VIEW, ProfileEvent::TYPE_SCAN, ProfileEvent::TYPE_SAVE,
            ])
            ->selectRaw('SUM(type = ?) v', [ProfileEvent::TYPE_VIEW])
            ->selectRaw('SUM(type = ?) s', [ProfileEvent::TYPE_SCAN])
            ->selectRaw('SUM(type = ?) e', [ProfileEvent::TYPE_SAVE])
            ->selectRaw('SUM(type = ?) p', [ProfileEvent::TYPE_SHARE])
            ->groupBy('jour')
            ->get()
            ->mapWithKeys(fn ($ligne) => [
                Carbon::parse($ligne->jour)->toDateString() => [
                    'vues' => (int) $ligne->v,
                    'scans' => (int) $ligne->s,
                    'saves' => (int) $ligne->e,
                    'partages' => (int) $ligne->p,
                    'total' => (int) $ligne->t,
                ],
            ]);
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
        // Au-delà de 60 jours, une barre par jour devient illisible : le
        // tableau de bord de l'administration ne garde que les 60 derniers
        // points. Ce plafond appartient à CET appelant, pas à la série
        // elle-même — l'espace client affiche jusqu'à 90 jours.
        return $this->serieDetaillee($depuis, min($jours, 60), $profileId)
            ->map(fn (array $jour) => $jour['total']);
    }

    /**
     * La même série, ventilée par type d'événement.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CETTE MÉTHODE EXISTE
     * ═══════════════════════════════════════════════════════════════════
     * L'espace client traçait sa propre courbe en groupant sur
     * `DATE(created_at)` dans `profile_events` — c'est-à-dire exactement
     * l'anti-patron que ce service a été écrit pour supprimer, et qui
     * coûtait 1,9 s sur un million de lignes. Le correctif avait été
     * appliqué à l'administration seulement.
     *
     * Une seule série existe désormais, et `serie()` n'en est plus qu'une
     * projection. Deux chemins de lecture pour un même chiffre finissent
     * toujours par diverger : celui qu'on optimise et celui qu'on oublie.
     *
     * @return Collection<string, array{vues:int, scans:int, saves:int, partages:int, total:int}>
     */
    public function serieDetaillee(Carbon $depuis, int $jours, ?int $profileId = null): Collection
    {
        $fenetre = max(1, $jours);
        $premierJour = Carbon::today()->subDays($fenetre - 1);

        /*
         | OÙ PASSE LA FRONTIÈRE ENTRE L'AGRÉGAT ET LA SOURCE
         |
         | Tout ce qui est agrégé se lit dans `profile_stats_daily`. Tout ce
         | qui vient APRÈS le dernier jour agrégé se lit dans la source —
         | y compris le jour en cours, qui n'est jamais agrégé puisqu'il
         | n'est pas terminé.
         |
         | En régime normal, cette portion vaut UN JOUR. Si le planificateur
         | est tombé, elle s'élargit d'autant de jours qu'il en a manqué, et
         | les chiffres restent exacts.
         */
        $dernierAgrege = $this->dernierJourAgrege();

        $debutSource = $dernierAgrege === null
            ? $premierJour                                  // rien n'a jamais été agrégé
            : Carbon::parse($dernierAgrege)->addDay();

        // Jamais avant le début de la fenêtre : on ne lit pas ce qu'on
        // n'affichera pas. Jamais après aujourd'hui non plus.
        if ($debutSource->lt($premierJour)) {
            $debutSource = $premierJour->copy();
        }

        if ($debutSource->gt(Carbon::today())) {
            $debutSource = Carbon::today();
        }

        $agregats = ProfileStatDaily::query()
            ->where('jour', '>=', $premierJour->toDateString())
            ->where('jour', '<', $debutSource->toDateString())
            ->when($profileId, fn ($q) => $q->where('profile_id', $profileId))
            ->groupBy('jour')
            ->selectRaw('jour')
            ->selectRaw('SUM(vues) vues, SUM(scans) scans, SUM(saves) saves, SUM(partages) partages, SUM(total) total')
            ->get()
            ->mapWithKeys(fn ($ligne) => [
                Carbon::parse($ligne->jour)->toDateString() => [
                    'vues' => (int) $ligne->vues,
                    'scans' => (int) $ligne->scans,
                    'saves' => (int) $ligne->saves,
                    'partages' => (int) $ligne->partages,
                    'total' => (int) $ligne->total,
                ],
            ]);

        $source = $this->depuisLaSource($debutSource, $profileId);

        $vide = ['vues' => 0, 'scans' => 0, 'saves' => 0, 'partages' => 0, 'total' => 0];
        $points = collect();

        // Du plus ancien au plus récent, sans trou : un jour sans événement
        // vaut zéro et doit occuper sa place, sinon les barres se tassent et
        // le graphique ment sur les creux.
        for ($i = $fenetre - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();

            $points[$date] = $date >= $debutSource->toDateString()
                ? ($source[$date] ?? $vide)
                : ($agregats[$date] ?? $vide);
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
