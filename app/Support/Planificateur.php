<?php

namespace App\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Throwable;

/**
 * DES TÂCHES QUI RATTRAPENT, PLUTÔT QUE DES TÂCHES À L'HEURE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE PROBLÈME QUE CETTE CLASSE RÉSOUT
 * ═══════════════════════════════════════════════════════════════════════════
 * `Schedule::command(...)->dailyAt('02:30')` signifie exactement : « pars si
 * l'on m'interroge pendant la minute 02:30 ». C'est un contrat de PRÉSENCE —
 * il suppose un processus qui interroge le planificateur chaque minute, sans
 * jamais s'arrêter.
 *
 * Sur le plan gratuit de Render, cette présence n'existe pas : le conteneur
 * s'endort dès qu'il n'y a plus de visiteurs, et le planificateur avec lui.
 * La minute 02:30 passe sans témoin, et la tâche n'est pas retardée — elle
 * est SAUTÉE. Sans erreur, sans trace, sans que rien ne le signale. C'est la
 * pire forme de panne : celle qu'on ne voit pas.
 *
 * On remplace donc le contrat de présence par un contrat d'ÉTAT :
 *
 *     avant   « il est 02:30 ? alors pars »
 *     après   « ai-je tourné aujourd'hui ? l'heure est-elle passée ? »
 *
 * Un conteneur endormi retarde désormais la tâche jusqu'à son réveil. Il ne
 * la saute plus jamais.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI TOUTES LES CINQ MINUTES ET NON CHAQUE MINUTE
 * ═══════════════════════════════════════════════════════════════════════════
 * La question « ai-je déjà tourné ? » coûte une lecture en base. La poser
 * chaque minute pour huit tâches ferait 11 520 lectures par jour ; toutes les
 * cinq minutes, 2 304. Pour des tâches QUOTIDIENNES, cinq minutes de retard
 * ne veulent rien dire — et l'instance tourne sur un dixième de processeur.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE MARQUEUR EST POSÉ APRÈS LA TENTATIVE, PAS APRÈS LE SUCCÈS
 * ═══════════════════════════════════════════════════════════════════════════
 * `after()` et non `onSuccess()`, et c'est un arbitrage assumé.
 *
 * Avec `onSuccess()`, une tâche qui échoue repartirait toutes les cinq
 * minutes jusqu'à minuit — 288 tentatives, 288 e-mails d'erreur, ou 288
 * appels à un service tiers en panne. Une panne se transformerait en salve.
 *
 * Avec `after()`, une tâche en échec attend le lendemain. La contrepartie est
 * réelle : un incident passager fait perdre la journée. On l'accepte parce
 * que l'échec, lui, reste visible — dans les journaux et sur l'écran « État
 * système » — alors qu'une salve, elle, fait des dégâts.
 */
final class Planificateur
{
    /** Le fuseau de référence du produit. Le serveur, lui, tourne en UTC. */
    public const FUSEAU = 'Africa/Dakar';

    private const TABLE = 'taches_planifiees';

    /** Clé du battement de cœur — « le planificateur tourne-t-il encore ? ». */
    public const BATTEMENT = '@battement';

    /**
     * Une tâche à exécuter une fois par jour, à partir de `$heure`.
     *
     * @param  string  $commande  la commande Artisan, arguments compris
     * @param  string  $heure  « 02:30 » — heure de Dakar, jamais UTC
     */
    public static function quotidienne(string $commande, string $heure): Event
    {
        return Schedule::command($commande)
            ->everyFiveMinutes()
            ->timezone(self::FUSEAU)
            ->when(fn () => self::estDueAujourdhui($commande, $heure))
            ->after(fn () => self::marquer($commande));
    }

    /**
     * Une tâche à exécuter une fois par semaine, à partir de `$heure`.
     *
     * LE JOUR DE LA SEMAINE N'EST PLUS FIXÉ, et c'est délibéré. `weeklyOn(0)`
     * épinglait la sauvegarde au dimanche ; un dimanche entier sans visiteur
     * la ferait sauter pour une semaine complète. « Au moins sept jours
     * depuis la dernière » garde l'intention — une sauvegarde par semaine —
     * sans dépendre d'un jour où personne ne passe.
     */
    public static function hebdomadaire(string $commande, string $heure): Event
    {
        return Schedule::command($commande)
            ->everyFiveMinutes()
            ->timezone(self::FUSEAU)
            ->when(fn () => self::estDueCetteSemaine($commande, $heure))
            ->after(fn () => self::marquer($commande));
    }

    /**
     * La tâche doit-elle partir maintenant ?
     *
     * Deux conditions, et l'ordre importe peu : l'heure de la journée doit
     * être atteinte, et la tâche ne doit pas avoir déjà tourné aujourd'hui.
     */
    public static function estDueAujourdhui(string $cle, string $heure): bool
    {
        $maintenant = Carbon::now(self::FUSEAU);

        if ($maintenant->lt(self::seuilDuJour($maintenant, $heure))) {
            return false;
        }

        return self::dernierJour($cle) !== $maintenant->toDateString();
    }

    /** Idem, mais la fenêtre est de sept jours au lieu d'une journée. */
    public static function estDueCetteSemaine(string $cle, string $heure): bool
    {
        $maintenant = Carbon::now(self::FUSEAU);

        if ($maintenant->lt(self::seuilDuJour($maintenant, $heure))) {
            return false;
        }

        $dernier = self::dernierJour($cle);

        return $dernier === null
            || Carbon::parse($dernier)->lte($maintenant->copy()->subDays(7));
    }

    /**
     * Le moment de la journée à partir duquel la tâche a le droit de partir.
     *
     * Une heure illisible ne doit pas empêcher une tâche de tourner : on
     * retombe sur minuit, ce qui la rend due dès le premier passage. Mieux
     * vaut une tâche qui part trop tôt qu'une tâche qui ne part jamais parce
     * qu'une chaîne de configuration comporte une faute de frappe.
     */
    private static function seuilDuJour(Carbon $maintenant, string $heure): Carbon
    {
        try {
            return $maintenant->copy()->setTimeFromTimeString($heure);
        } catch (Throwable) {
            return $maintenant->copy()->startOfDay();
        }
    }

    /** Le dernier jour où la tâche a été tentée, au format « 2026-09-02 ». */
    public static function dernierJour(string $cle): ?string
    {
        $valeur = DB::table(self::TABLE)->where('cle', $cle)->value('dernier_jour');

        return $valeur === null ? null : Carbon::parse($valeur)->toDateString();
    }

    /** Enregistre que la tâche vient d'être tentée. */
    public static function marquer(string $cle): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['cle' => $cle],
            [
                'dernier_jour' => Carbon::now(self::FUSEAU)->toDateString(),
                'mis_a_jour_le' => Carbon::now(),
            ],
        );
    }

    /**
     * LE BATTEMENT DE CŒUR — la preuve que le planificateur tourne.
     *
     * Sans lui, un planificateur arrêté ressemble en tout point à un
     * planificateur qui n'a rien à faire : les deux ne produisent rien. Cette
     * ligne, mise à jour à chaque passage, permet à l'écran « État système »
     * de dire « dernier passage il y a trois minutes » plutôt que de laisser
     * deviner.
     */
    public static function battre(): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['cle' => self::BATTEMENT],
            [
                'dernier_jour' => Carbon::now(self::FUSEAU)->toDateString(),
                'mis_a_jour_le' => Carbon::now(),
            ],
        );
    }

    /**
     * Depuis combien de minutes le planificateur n'a-t-il pas donné signe ?
     *
     * `null` quand il n'a jamais tourné — ce qui n'est pas la même chose que
     * « il vient de tourner », et l'écran d'état doit pouvoir les distinguer.
     */
    public static function minutesDepuisLeDernierBattement(): ?int
    {
        $valeur = DB::table(self::TABLE)
            ->where('cle', self::BATTEMENT)
            ->value('mis_a_jour_le');

        return $valeur === null ? null : (int) Carbon::parse($valeur)->diffInMinutes(Carbon::now());
    }
}
