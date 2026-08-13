<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\MailLog;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LE RÉCAPITULATIF DE LA JOURNÉE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CE SERVICE N'EST PAS AdminStatsService
 * ═══════════════════════════════════════════════════════════════════════
 * Celui-ci raisonne en périodes de 7 à 365 jours et compare à la période
 * précédente de même durée. Le récapitulatif, lui, compare UN JOUR à LA
 * VEILLE — un intervalle que l'autre ne sait pas produire, et qu'ajouter
 * l'obligerait à porter deux logiques de comparaison.
 *
 * Ce qui est partagé, en revanche, ce sont les définitions : « un essai en
 * cours » et « un paiement réussi » veulent dire ici exactement ce qu'ils
 * veulent dire sur l'écran d'administration. Deux définitions divergentes
 * donneraient deux chiffres différents pour la même chose, et personne ne
 * saurait lequel croire.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * TOUT EST CALCULÉ EN SQL, ET LES COMPTEURS D'UN JOUR EN UNE REQUÊTE
 * ═══════════════════════════════════════════════════════════════════════
 * Les agrégats conditionnels (CASE WHEN) permettent de compter aujourd'hui ET
 * hier en un seul balayage par table. Deux requêtes séparées coûteraient le
 * double et — c'est le vrai motif — prendraient deux photographies à deux
 * instants différents : sur une base active, la comparaison porterait alors
 * sur deux états qui n'ont jamais coexisté.
 *
 * `CASE WHEN` est employé plutôt que `SUM(condition)` : MySQL et SQLite
 * acceptent le second, PostgreSQL non. La base de production peut encore
 * changer ; la forme portable ne coûte rien.
 */
class RapportQuotidien
{
    private CarbonImmutable $debutJour;

    private CarbonImmutable $debutVeille;

    /**
     * @param  CarbonImmutable|null  $jour  la journée à couvrir ; aujourd'hui par défaut
     */
    public function __construct(?CarbonImmutable $jour = null)
    {
        // Figé à la construction : deux lectures du même rapport doivent
        // porter sur exactement le même intervalle.
        $reference = ($jour ?? CarbonImmutable::now())->startOfDay();

        $this->debutJour = $reference;
        $this->debutVeille = $reference->subDay();
    }

    public function jour(): CarbonImmutable
    {
        return $this->debutJour;
    }

    /**
     * Les chiffres de la journée, chacun avec celui de la veille.
     *
     * @return array<string, array{libelle:string, valeur:int, veille:int, unite:?string}>
     */
    public function chiffres(): array
    {
        $comptes = $this->parJour(User::query()->where('role', User::ROLE_USER));
        $cartes = $this->parJour(Profile::query());
        $publiees = $this->parJour(Profile::query()->where('is_active', true), 'updated_at');
        $paiements = $this->paiements();
        $messages = $this->messagesDeContact();

        return [
            'comptes' => [
                'libelle' => 'Nouveaux comptes',
                'valeur' => $comptes['jour'],
                'veille' => $comptes['veille'],
                'unite' => null,
            ],
            'cartes' => [
                'libelle' => 'Cartes créées',
                'valeur' => $cartes['jour'],
                'veille' => $cartes['veille'],
                'unite' => null,
            ],
            'publiees' => [
                'libelle' => 'Cartes mises en ligne',
                'valeur' => $publiees['jour'],
                'veille' => $publiees['veille'],
                'unite' => null,
            ],
            'paiements' => [
                'libelle' => 'Paiements encaissés',
                'valeur' => $paiements['nombre_jour'],
                'veille' => $paiements['nombre_veille'],
                'unite' => null,
            ],
            'recettes' => [
                'libelle' => 'Recettes',
                'valeur' => $paiements['montant_jour'],
                'veille' => $paiements['montant_veille'],
                'unite' => 'FCFA',
            ],
            'messages' => [
                'libelle' => 'Messages reçus',
                'valeur' => $messages['jour'],
                'veille' => $messages['veille'],
                'unite' => null,
            ],
        ];
    }

    /**
     * L'état à cet instant — pas un flux de la journée, une photographie.
     *
     * @return array<string, int>
     */
    public function etat(): array
    {
        return [
            'abonnements_actifs' => Subscription::query()->active()->count(),

            // Même définition que l'écran d'administration : c'est le PRIX de
            // la formule qui fait l'essai, pas un drapeau sur l'abonnement.
            'essais' => Subscription::query()
                ->active()
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('plans.price_fcfa', 0)
                ->count(),

            'cartes_en_ligne' => Profile::query()->where('is_active', true)->count(),
        ];
    }

    /**
     * CE QUI APPELLE UNE ACTION — en tête du message, ou rien.
     *
     * Trois motifs seulement, tous vérifiables et tous actionnables. Un
     * récapitulatif qui signale ce sur quoi on ne peut rien agir devient un
     * bulletin météo : on cesse de le lire, et le jour où il dit quelque chose
     * d'important, personne ne le voit.
     *
     * @return array<int, string>
     */
    public function alertes(): array
    {
        $alertes = [];

        /*
         | PAIEMENTS EN ATTENTE DEPUIS PLUS D'UNE HEURE.
         |
         | Une heure, et non vingt-quatre comme sur l'écran d'administration :
         | ce message part une fois par jour, un seuil de 24 h laisserait
         | passer une journée entière avant le premier signalement.
         */
        $bloques = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->where('created_at', '<', CarbonImmutable::now()->subHour())
            ->count();

        if ($bloques > 0) {
            $alertes[] = $bloques.' paiement'.($bloques > 1 ? 's sont bloqués' : ' est bloqué')
                .' depuis plus d\'une heure';
        }

        /*
         | E-MAILS EN ÉCHEC AUJOURD'HUI.
         |
         | Ce motif existe à cause d'une panne réelle : l'envoi a cessé de
         | fonctionner pendant trois jours sans que rien ne le signale. Une
         | ligne ici l'aurait rendu visible le premier soir.
         */
        $mailsRates = MailLog::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', $this->debutJour)
            ->count();

        if ($mailsRates > 0) {
            $alertes[] = $mailsRates.' e-mail'.($mailsRates > 1 ? 's ne sont' : ' n\'est')
                .' pas parti'.($mailsRates > 1 ? 's' : '');
        }

        // Traitements en échec définitif. La table peut ne pas exister sur une
        // installation qui n'a jamais utilisé la file : on ne suppose rien.
        if (Schema::hasTable('failed_jobs')) {
            $travaux = DB::table('failed_jobs')
                ->where('failed_at', '>=', $this->debutJour)
                ->count();

            if ($travaux > 0) {
                $alertes[] = $travaux.' traitement'.($travaux > 1 ? 's ont' : ' a')
                    .' échoué définitivement';
            }
        }

        return $alertes;
    }

    /** La journée n'a-t-elle rien produit du tout ? */
    public function journeeVide(): bool
    {
        foreach ($this->chiffres() as $chiffre) {
            if ($chiffre['valeur'] > 0) {
                return false;
            }
        }

        return true;
    }

    // -----------------------------------------------------------------------

    /**
     * Compte les lignes du jour et de la veille EN UNE SEULE REQUÊTE.
     *
     * @return array{jour:int, veille:int}
     */
    private function parJour($requete, string $colonne = 'created_at'): array
    {
        $table = $requete->getModel()->getTable();
        $champ = $table.'.'.$colonne;

        $ligne = $requete
            ->selectRaw($this->compteSi($champ, $this->debutJour, null).' as jour')
            ->selectRaw($this->compteSi($champ, $this->debutVeille, $this->debutJour).' as veille')
            ->first();

        return [
            'jour' => (int) ($ligne->jour ?? 0),
            'veille' => (int) ($ligne->veille ?? 0),
        ];
    }

    /**
     * Nombre ET montant des paiements réussis, jour et veille — une requête.
     *
     * @return array{nombre_jour:int, nombre_veille:int, montant_jour:int, montant_veille:int}
     */
    private function paiements(): array
    {
        $ligne = Payment::query()
            ->successful()
            ->selectRaw($this->compteSi('created_at', $this->debutJour, null).' as n_jour')
            ->selectRaw($this->compteSi('created_at', $this->debutVeille, $this->debutJour).' as n_veille')
            ->selectRaw($this->sommeSi('amount_fcfa', 'created_at', $this->debutJour, null).' as m_jour')
            ->selectRaw($this->sommeSi('amount_fcfa', 'created_at', $this->debutVeille, $this->debutJour).' as m_veille')
            ->first();

        return [
            'nombre_jour' => (int) ($ligne->n_jour ?? 0),
            'nombre_veille' => (int) ($ligne->n_veille ?? 0),
            'montant_jour' => (int) ($ligne->m_jour ?? 0),
            'montant_veille' => (int) ($ligne->m_veille ?? 0),
        ];
    }

    /** @return array{jour:int, veille:int} */
    private function messagesDeContact(): array
    {
        return $this->parJour(ContactMessage::query());
    }

    /**
     * COUNT conditionnel, portable.
     *
     * `SUM(condition)` serait plus court mais ne fonctionne ni sur PostgreSQL
     * ni sur SQL Server : la comparaison y rend un booléen, pas un entier. La
     * base de production peut encore changer ; la forme portable ne coûte rien.
     */
    private function compteSi(string $colonne, CarbonImmutable $de, ?CarbonImmutable $a): string
    {
        $borne = $a === null
            ? ''
            : " AND {$colonne} < '".$a->toDateTimeString()."'";

        return "COUNT(CASE WHEN {$colonne} >= '".$de->toDateTimeString()."'{$borne} THEN 1 END)";
    }

    /** Somme conditionnelle, même raisonnement. COALESCE : une somme vide vaut 0. */
    private function sommeSi(string $valeur, string $colonne, CarbonImmutable $de, ?CarbonImmutable $a): string
    {
        $borne = $a === null
            ? ''
            : " AND {$colonne} < '".$a->toDateTimeString()."'";

        return "COALESCE(SUM(CASE WHEN {$colonne} >= '".$de->toDateTimeString()."'{$borne} THEN {$valeur} ELSE 0 END), 0)";
    }
}
