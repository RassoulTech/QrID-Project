<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * FILTRE DE PÉRIODE — une liste déroulante, pas deux champs de date.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI PAS « DU … AU … »
 * ═══════════════════════════════════════════════════════════════════════
 * Deux champs de date paraissent plus puissants. À l'usage, ils coûtent
 * cher pour ce qu'ils rendent :
 *
 *   · il faut DEUX saisies pour obtenir un résultat, et une seule des deux
 *     ne filtre presque rien — on tape « du 01/01 » et on obtient tout ;
 *   · le sélecteur natif s'ouvre en plein écran sur téléphone, deux fois ;
 *   · rien n'empêche de saisir une fin antérieure au début : la liste se
 *     vide sans que l'écran dise pourquoi ;
 *   · le format jj/mm/aaaa change d'un navigateur à l'autre, le champ vide
 *     affiche « jj/mm/aaaa » en gris et se lit comme un bug d'affichage.
 *
 * Or ce que l'on cherche réellement tient presque toujours en un mot :
 * aujourd'hui, cette semaine, ce mois-ci. Un choix, une frappe, aucun
 * intervalle incohérent possible.
 *
 * Les périodes RELATIVES (7, 30, 90 jours) et CALENDAIRES (ce mois-ci, le
 * mois dernier) coexistent parce qu'elles répondent à deux questions
 * différentes : « que s'est-il passé récemment » et « qu'a donné janvier ».
 *
 * ═══════════════════════════════════════════════════════════════════════
 * UN SEUL ENDROIT
 * ═══════════════════════════════════════════════════════════════════════
 * Les libellés, les bornes et l'application à la requête vivent ici. Un
 * écran qui propose « 30 derniers jours » et un autre « 1 mois » finiraient
 * par ne pas filtrer la même chose, sans que personne ne le remarque.
 */
final class FiltrePeriode
{
    public const TOUT = '';

    public const AUJOURDHUI = 'aujourdhui';

    public const HIER = 'hier';

    public const SEPT_JOURS = '7j';

    public const TRENTE_JOURS = '30j';

    public const QUATRE_VINGT_DIX_JOURS = '90j';

    public const CE_MOIS = 'ce-mois';

    public const MOIS_DERNIER = 'mois-dernier';

    public const CETTE_ANNEE = 'cette-annee';

    /**
     * Libellés dans l'ordre d'affichage : du plus court au plus long, les
     * périodes calendaires à la fin. On descend la liste en élargissant.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::TOUT => 'Toutes les périodes',
            self::AUJOURDHUI => "Aujourd'hui",
            self::HIER => 'Hier',
            self::SEPT_JOURS => '7 derniers jours',
            self::TRENTE_JOURS => '30 derniers jours',
            self::QUATRE_VINGT_DIX_JOURS => '90 derniers jours',
            self::CE_MOIS => 'Ce mois-ci',
            self::MOIS_DERNIER => 'Le mois dernier',
            self::CETTE_ANNEE => 'Cette année',
        ];
    }

    public static function libelle(?string $cle): string
    {
        return self::options()[$cle ?? self::TOUT] ?? self::options()[self::TOUT];
    }

    public static function valide(?string $cle): string
    {
        return array_key_exists((string) $cle, self::options()) ? (string) $cle : self::TOUT;
    }

    /**
     * Bornes de la période, ou null quand elle ne borne rien.
     *
     * @return array{CarbonImmutable, CarbonImmutable}|null
     */
    public static function bornes(?string $cle): ?array
    {
        $maintenant = CarbonImmutable::now();

        return match (self::valide($cle)) {
            self::AUJOURDHUI => [$maintenant->startOfDay(), $maintenant->endOfDay()],
            self::HIER => [
                $maintenant->subDay()->startOfDay(),
                $maintenant->subDay()->endOfDay(),
            ],
            self::SEPT_JOURS => [$maintenant->subDays(7), $maintenant],
            self::TRENTE_JOURS => [$maintenant->subDays(30), $maintenant],
            self::QUATRE_VINGT_DIX_JOURS => [$maintenant->subDays(90), $maintenant],
            self::CE_MOIS => [$maintenant->startOfMonth(), $maintenant->endOfMonth()],
            self::MOIS_DERNIER => [
                $maintenant->subMonth()->startOfMonth(),
                $maintenant->subMonth()->endOfMonth(),
            ],
            self::CETTE_ANNEE => [$maintenant->startOfYear(), $maintenant->endOfYear()],
            default => null,
        };
    }

    /**
     * Applique la période à une requête.
     *
     * LA COLONNE EST QUALIFIÉE PAR L'APPELANT — « payments.created_at » et non
     * « created_at ». Sur une requête qui joint deux tables portant toutes
     * deux created_at, la forme courte fait échouer la requête entière pour
     * ambiguïté. C'est arrivé une fois sur cet écran, on ne recommence pas.
     */
    public static function appliquer(Builder $requete, ?string $cle, string $colonne): Builder
    {
        $bornes = self::bornes($cle);

        if ($bornes === null) {
            return $requete;
        }

        return $requete->whereBetween($colonne, $bornes);
    }
}
