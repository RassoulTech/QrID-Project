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
 * POURQUOI TOUTES LES TRENTE MINUTES
 * ═══════════════════════════════════════════════════════════════════════════
 * La question « ai-je déjà tourné ? » coûte une lecture en base. On la
 * posait toutes les cinq minutes ; c'était raisonnable sur MySQL, et c'est
 * devenu un coût sur Neon.
 *
 * Neon endort la base après cinq minutes SANS AUCUNE requête, et ne facture
 * — ou ne décompte de l'offre gratuite — que le temps où elle est éveillée.
 * Une question toutes les cinq minutes la tenait éveillée en permanence dès
 * que le conteneur l'était : ~186 heures de calcul par mois pour 100
 * offertes, sans un seul visiteur.
 *
 * Toutes les trente minutes, la base dort vingt-cinq minutes sur trente
 * quand personne n'utilise le produit. Le prix : une tâche quotidienne part
 * au plus une demi-heure après son heure — le récapitulatif de 21 h arrive
 * avant 21 h 30. Pour des tâches QUOTIDIENNES, c'est sans conséquence.
 *
 * MAIS « TOUTES LES TRENTE MINUTES » NE VEUT PAS DIRE « À :00 ET :30 ».
 * Un conteneur gratuit dort quinze minutes après la dernière visite : calé
 * sur l'horloge, le planificateur manquait la moitié de ses rendez-vous, et
 * parfois tous. Il compte donc depuis SON dernier passage, et passe dès le
 * réveil — voir passageOuvert().
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE MARQUEUR EST POSÉ APRÈS LA TENTATIVE, PAS APRÈS LE SUCCÈS
 * ═══════════════════════════════════════════════════════════════════════════
 * `after()` et non `onSuccess()`, et c'est un arbitrage assumé.
 *
 * Avec `onSuccess()`, une tâche qui échoue repartirait à chaque passage
 * jusqu'à minuit — des dizaines de tentatives, autant d'e-mails d'erreur, ou
 * d'appels à un service tiers en panne. Une panne se transformerait en salve.
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
     * Le rythme auquel le planificateur interroge la base : tâches et
     * battement. Voir « POURQUOI TOUTES LES TRENTE MINUTES » plus haut.
     */
    public const CADENCE_MINUTES = 30;

    /**
     * CHAQUE MINUTE, MAIS SANS TOUCHER LA BASE — et c'est passageOuvert()
     * qui décide.
     *
     * L'expression valait « *\/30 » : le planificateur ne regardait la base
     * qu'aux minutes :00 et :30. Or un conteneur gratuit de Render ne vit
     * qu'une quinzaine de minutes après la dernière visite. Le 13 octobre,
     * éveillé de 12 h 02 à 12 h 20 puis de 12 h 33 à 12 h 49, il a manqué
     * les deux rendez-vous : aucune tâche, aucun battement, sans une erreur.
     *
     * Interrogé chaque minute, l'événement passe désormais par un filtre qui
     * ne lit qu'un fichier local. La base n'est sollicitée qu'au premier
     * passage après le réveil, puis toutes les trente minutes.
     */
    public const EXPRESSION = '* * * * *';

    /** Où passageOuvert() note son dernier passage. Remplaçable en test. */
    public static ?string $temoin = null;

    /** @var array<string, bool> le verdict, une fois par minute et par processus */
    private static array $verdicts = [];

    /**
     * Au-delà de ce silence, le planificateur est tenu pour arrêté : deux
     * battements manqués, plus une marge pour le réveil d'un conteneur.
     */
    public const SILENCE_ANORMAL_MINUTES = 2 * self::CADENCE_MINUTES + 5;

    /**
     * Une tâche à exécuter une fois par jour, à partir de `$heure`.
     *
     * @param  string  $commande  la commande Artisan, arguments compris
     * @param  string  $heure  « 02:30 » — heure de Dakar, jamais UTC
     */
    public static function quotidienne(string $commande, string $heure): Event
    {
        return self::visible(Schedule::command($commande))
            ->when(fn () => self::reclamerAujourdhui($commande, $heure));
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
        return self::visible(Schedule::command($commande))
            ->when(fn () => self::reclamerCetteSemaine($commande, $heure));
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

    /**
     * LA SORTIE D'UNE TÂCHE PLANIFIÉE NE DOIT PAS PARTIR AU NÉANT.
     *
     * ═══════════════════════════════════════════════════════════════════
     * CE QUE CE DÉFAUT A COÛTÉ — DEUX FOIS
     * ═══════════════════════════════════════════════════════════════════
     * Laravel exécute une tâche planifiée en redirigeant sa sortie vers
     * `/dev/null`. Le journal ne garde alors qu'une ligne :
     *
     *     Scheduled command [...] failed with exit code [1]
     *
     * Un code de sortie et rien d'autre. Ni le message, ni la cause, ni ce
     * qui manquait. À la mise en service du planificateur, deux tâches ont
     * échoué — et il a fallu lire leur code source pour deviner pourquoi,
     * alors qu'elles l'avaient EXPLIQUÉ dans une sortie que personne ne
     * lisait.
     *
     * LA SECONDE FOIS, C'EST MOI QUI L'AI SUPPRIMÉE. Une réécriture de
     * `quotidienne()` et `hebdomadaire()` — pour rendre la réclamation
     * atomique — a remplacé l'appel à cette méthode par la chaîne
     * d'origine. Aucun test ne la couvrait : la régression est partie en
     * production sans que rien ne la signale, et la sortie des tâches est
     * retournée au néant pendant une journée entière.
     *
     * C'est exactement la classe de panne que ce fichier existe pour
     * supprimer. `PlanificateurTest` la verrouille désormais.
     *
     * `/dev/stdout` parce que LOG_CHANNEL vaut `stderr` en production :
     * Render collecte les deux flux du conteneur. Ce qu'une commande écrit
     * arrive donc dans les journaux du service, à côté de l'erreur qu'il
     * explique.
     *
     * `appendOutputTo` et non `sendOutputTo` : le second TRONQUE sa cible
     * avant d'écrire, ce qui n'a aucun sens sur un flux.
     */
    private static function visible(Event $tache): Event
    {
        return $tache
            ->cron(self::EXPRESSION)
            ->timezone(self::FUSEAU)
            // EN PREMIER : Laravel évalue les filtres dans l'ordre et s'arrête
            // au premier refus. Hors passage, la réclamation — une écriture
            // en base — n'est donc jamais tentée.
            ->when(fn () => self::passageOuvert())
            ->appendOutputTo('/dev/stdout');
    }

    /**
     * LE PLANIFICATEUR A-T-IL LE DROIT DE PARLER À LA BASE, CETTE MINUTE ?
     *
     * Oui au premier passage d'un conteneur — le témoin vit sur son disque
     * local, que Render recrée à chaque réveil — puis une fois toutes les
     * CADENCE_MINUTES. Non le reste du temps, et sans la moindre requête :
     * c'est ce qui laisse Neon s'endormir entre deux passages.
     *
     * Le verdict est mémorisé pour la minute : toutes les tâches d'un même
     * `schedule:run` doivent recevoir la même réponse. Sans cela, la
     * première ouvrirait le passage en écrivant le témoin, et les suivantes,
     * le trouvant tout frais, se le verraient refuser.
     *
     * Un témoin illisible ou impossible à écrire ouvre le passage : mieux
     * vaut une base réveillée pour rien qu'une journée de tâches sautée.
     */
    public static function passageOuvert(): bool
    {
        $maintenant = Carbon::now();
        $temoin = self::$temoin ?? storage_path('framework/planificateur-passage');
        $minute = $temoin.'|'.$maintenant->format('Y-m-d H:i');

        if (array_key_exists($minute, self::$verdicts)) {
            return self::$verdicts[$minute];
        }

        $dernier = is_file($temoin) ? (int) @file_get_contents($temoin) : 0;

        // Trente secondes de tolérance : schedule:work se cale sur le début
        // de chaque minute, et un passage écrit à 12:00:02 doit rouvrir à
        // 12:30:00, pas à 12:31.
        $ouvert = $maintenant->getTimestamp() - $dernier >= self::CADENCE_MINUTES * 60 - 30;

        if ($ouvert) {
            @file_put_contents($temoin, (string) $maintenant->getTimestamp());
        }

        // Une seule minute en mémoire : le processus de `schedule:work` ne
        // vit qu'une minute, mais celui d'un test ou de la route
        // d'automatisation peut durer.
        self::$verdicts = [$minute => $ouvert];

        return $ouvert;
    }

    /**
     * RÉCLAMER LA JOURNÉE — et savoir si on l'a emportée.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI « LIRE PUIS ÉCRIRE » NE SUFFISAIT PAS
     * ═══════════════════════════════════════════════════════════════════
     * Le dispositif lisait le marqueur pour décider, puis l'écrivait une
     * fois la tâche finie. Entre les deux, une seconde exécution pouvait
     * lire le même « pas encore fait » et partir elle aussi.
     *
     * Ce n'est pas théorique. Deux appelants coexistent :
     *
     *   · `schedule:work`, dans le conteneur, chaque minute ;
     *   · la route /automation/schedule, appelée de l'extérieur — elle
     *     existait AVANT ce dispositif et reste un filet utile le jour où
     *     le conteneur dort.
     *
     * Deux récapitulatifs Discord de la même journée, ou deux salves de
     * relances aux mêmes clients : voilà ce que la course produisait.
     *
     * ═══════════════════════════════════════════════════════════════════
     * COMMENT LA BASE TRANCHE À NOTRE PLACE
     * ═══════════════════════════════════════════════════════════════════
     * Un seul UPDATE conditionnel. Le moteur verrouille la ligne le temps
     * de l'écriture : le premier arrivant voit sa condition satisfaite et
     * obtient une ligne modifiée ; le second trouve la condition déjà
     * fausse et en obtient zéro. Personne n'arbitre — la base le fait.
     *
     * Le marquage a donc lieu AVANT l'exécution, et non après. C'est la
     * même décision qu'auparavant, poussée à sa conclusion : une tâche qui
     * échoue attend le lendemain plutôt que de repartir à chaque
     * passage. Réclamer d'abord, c'est aussi ce qui rend la réclamation
     * atomique — on ne peut pas verrouiller ce qu'on n'a pas encore écrit.
     */
    public static function reclamerAujourdhui(string $cle, string $heure): bool
    {
        $maintenant = Carbon::now(self::FUSEAU);

        if ($maintenant->lt(self::seuilDuJour($maintenant, $heure))) {
            return false;
        }

        return self::reclamer($cle, $maintenant->toDateString(), fn ($q) => $q
            ->whereNull('dernier_jour')
            ->orWhere('dernier_jour', '<>', $maintenant->toDateString()));
    }

    /** Idem, sur une fenêtre de sept jours. */
    public static function reclamerCetteSemaine(string $cle, string $heure): bool
    {
        $maintenant = Carbon::now(self::FUSEAU);

        if ($maintenant->lt(self::seuilDuJour($maintenant, $heure))) {
            return false;
        }

        $ilYASeptJours = $maintenant->copy()->subDays(7)->toDateString();

        return self::reclamer($cle, $maintenant->toDateString(), fn ($q) => $q
            ->whereNull('dernier_jour')
            ->orWhere('dernier_jour', '<=', $ilYASeptJours));
    }

    /**
     * L'écriture qui tranche.
     *
     * `insertOrIgnore` d'abord : l'UPDATE ne peut rien verrouiller si la
     * ligne n'existe pas encore. Il n'écrase jamais un marqueur existant —
     * c'est tout l'intérêt du « ignore ».
     */
    private static function reclamer(string $cle, string $jour, \Closure $condition): bool
    {
        DB::table(self::TABLE)->insertOrIgnore([
            'cle' => $cle,
            'dernier_jour' => null,
            'mis_a_jour_le' => null,
        ]);

        $lignes = DB::table(self::TABLE)
            ->where('cle', $cle)
            ->where($condition)
            ->update([
                'dernier_jour' => $jour,
                'mis_a_jour_le' => Carbon::now(),
            ]);

        return $lignes === 1;
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

        self::direOuEnEstLaFile();
    }

    /**
     * LA PROFONDEUR DE LA FILE, DITE À VOIX HAUTE À CHAQUE BATTEMENT.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CETTE LIGNE EXISTE
     * ═══════════════════════════════════════════════════════════════════
     * Le produit a un worker qui tourne, et aucun moyen de savoir s'il
     * CONSOMME. Les deux états se ressemblent exactement de l'extérieur :
     * un processus vivant qui avale les tâches et un processus vivant qui
     * regarde ailleurs produisent le même silence dans les journaux.
     *
     * La différence n'apparaît qu'au moment de basculer QUEUE_CONNECTION —
     * et si l'on s'est trompé, elle apparaît sous la forme « plus personne
     * ne peut créer de compte », sans la moindre erreur nulle part. C'est
     * la panne que ce produit a déjà vécue.
     *
     * Deux entiers à chaque battement suffisent à la rendre visible :
     * si `en_attente` monte sans jamais redescendre, le worker ne consomme
     * pas. S'il reste à zéro pendant que des e-mails partent, il consomme.
     *
     * ═══════════════════════════════════════════════════════════════════
     * ELLE NE DOIT JAMAIS CASSER LE BATTEMENT
     * ═══════════════════════════════════════════════════════════════════
     * Le battement sert à prouver que le planificateur tourne. Si la
     * lecture de la file échoue — table absente sur une installation
     * neuve, base momentanément injoignable — c'est la lecture qu'on perd,
     * jamais la preuve de vie.
     */
    private static function direOuEnEstLaFile(): void
    {
        try {
            $enAttente = DB::table('jobs')->count();
            $echouees = DB::table('failed_jobs')->count();
        } catch (Throwable) {
            return;   // la file n'est pas lisible : le battement, lui, l'est
        }

        // Rien en attente et rien en échec : le cas nominal, et il n'a pas
        // besoin d'une ligne de journal à chaque battement.
        if ($enAttente === 0 && $echouees === 0) {
            return;
        }

        echo sprintf(
            'File : %d en attente, %d en échec.'.PHP_EOL,
            $enAttente,
            $echouees,
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
