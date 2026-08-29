<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agrégations de la vue d'ensemble.
 *
 * TOUT EST CALCULÉ EN SQL. Aucune boucle PHP sur des lignes : compter 80 000
 * événements en mémoire pour en tirer six chiffres ferait tomber l'écran bien
 * avant que le produit ne devienne intéressant. `selectRaw` + `groupBy`, et le
 * moteur rend une ligne par point.
 *
 * COMPTE DES REQUÊTES — 26, mesurées sur l'écran complet.
 *
 *   6 cartes ....................... 18   (3 chacune : total, période
 *                                          courante, période précédente)
 *   histogramme des inscriptions ...  1
 *   répartition des moyens .........  1
 *   dernières inscriptions .........  1
 *   derniers paiements .............  2   (lignes + relation user)
 *   alerte contextuelle ............  2
 *   compte connecté ................  1
 *
 * LES TROIS REQUÊTES PAR CARTE SONT LE PRIX DE LA VARIATION. Afficher
 * « +12 % » suppose de compter deux intervalles, et le total affiché n'est
 * pas la somme des deux — il porte sur toute la base, pas sur la période.
 * Les fusionner en une requête à trois agrégats conditionnels est faisable
 * et divisera ce chiffre par trois ; ce n'est pas fait, et c'est le premier
 * endroit à regarder si cet écran ralentit.
 *
 * Aucune de ces requêtes n'est un N+1 : leur nombre est fixe, il ne croît
 * ni avec le nombre de comptes ni avec celui des paiements.
 *
 * Le service est SANS ÉTAT entre deux requêtes HTTP, mais mémorise dans
 * l'instance : la période est lue plusieurs fois par le même rendu, et
 * recalculer les bornes à chaque appel ferait diverger les chiffres si la
 * seconde changeait entre deux d'entre eux.
 */
class AdminStatsService
{
    /** Périodes proposées par le sélecteur, en jours. */
    public const PERIODES = [
        '7j' => 7,
        '30j' => 30,
        '90j' => 90,
        '12m' => 365,
    ];

    public const PERIODE_DEFAUT = '30j';

    private CarbonImmutable $fin;

    private CarbonImmutable $debut;

    private int $jours;

    public function __construct(?string $periode = null)
    {
        $this->jours = self::PERIODES[$periode ?? self::PERIODE_DEFAUT] ?? self::PERIODES[self::PERIODE_DEFAUT];

        // Figé à la construction : deux appels dans le même rendu doivent
        // porter exactement sur le même intervalle.
        $this->fin = CarbonImmutable::now();
        $this->debut = $this->fin->subDays($this->jours);
    }

    public static function periodeValide(?string $periode): string
    {
        return isset(self::PERIODES[$periode]) ? $periode : self::PERIODE_DEFAUT;
    }

    public function jours(): int
    {
        return $this->jours;
    }

    public function debut(): CarbonImmutable
    {
        return $this->debut;
    }

    public function fin(): CarbonImmutable
    {
        return $this->fin;
    }

    // =======================================================================
    // LES SIX CARTES
    // =======================================================================

    /**
     * Chaque carte porte sa valeur ET sa variation par rapport à la période
     * précédente de même durée. Une variation sans point de comparaison
     * explicite ne veut rien dire ; ici, « +12 % » signifie toujours « contre
     * les 30 jours d'avant ».
     *
     * @return array<string, array{valeur:int, variation:?float}>
     */
    public function cartes(): array
    {
        return [
            'utilisateurs' => $this->avecVariation(
                fn ($de, $a) => User::query()->where('role', User::ROLE_USER)
                    ->whereBetween('created_at', [$de, $a])->count(),
                User::query()->where('role', User::ROLE_USER)->count(),
            ),

            'profils' => $this->avecVariation(
                fn ($de, $a) => Profile::query()->whereBetween('created_at', [$de, $a])->count(),
                Profile::query()->count(),
            ),

            'abonnements_actifs' => $this->avecVariation(
                fn ($de, $a) => Subscription::query()->where('status', Subscription::STATUS_ACTIVE)
                    ->whereBetween('created_at', [$de, $a])->count(),
                Subscription::query()->active()->count(),
            ),

            'chiffre_affaires' => $this->avecVariation(
                fn ($de, $a) => (int) Payment::query()->successful()
                    ->whereBetween('created_at', [$de, $a])->sum('amount_fcfa'),
                (int) Payment::query()->successful()
                    ->where('created_at', '>=', $this->debut)->sum('amount_fcfa'),
            ),

            'essais' => $this->avecVariation(
                fn ($de, $a) => $this->essaisEnCours()->whereBetween('subscriptions.created_at', [$de, $a])->count(),
                $this->essaisEnCours()->count(),
            ),

            'paiements_attente' => $this->avecVariation(
                fn ($de, $a) => Payment::query()->where('status', Payment::STATUS_PENDING)
                    ->whereBetween('created_at', [$de, $a])->count(),
                Payment::query()->where('status', Payment::STATUS_PENDING)->count(),
            ),
        ];
    }

    /**
     * Un essai en cours = abonnement actif sur une formule gratuite.
     * Le prix de la formule fait foi, pas un drapeau sur l'abonnement : le
     * schéma ne connaît qu'un seul type d'abonnement.
     */
    private function essaisEnCours()
    {
        return Subscription::query()
            ->active()
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('plans.price_fcfa', 0);
    }

    /**
     * @param  callable(CarbonImmutable, CarbonImmutable): int  $surIntervalle
     * @return array{valeur:int, variation:?float}
     */
    private function avecVariation(callable $surIntervalle, int $valeur): array
    {
        $courant = $surIntervalle($this->debut, $this->fin);
        $precedent = $surIntervalle($this->debut->subDays($this->jours), $this->debut);

        return [
            'valeur' => $valeur,
            // Partir de zéro n'est pas « +100 % », c'est incomparable. On
            // renvoie null et l'écran affiche un tiret plutôt qu'un chiffre
            // qui impressionne sans rien dire.
            'variation' => $precedent === 0 ? null : round((($courant - $precedent) / $precedent) * 100, 1),
        ];
    }

    // =======================================================================
    // TENDANCE DES INSCRIPTIONS
    // =======================================================================

    /**
     * Un point par jour, ou par mois au-delà de 90 jours — 365 barres sur un
     * histogramme large de 600 pixels ne se lisent pas.
     *
     * Les jours sans inscription sont REMPLIS À ZÉRO. Un GROUP BY ne rend que
     * les jours peuplés : sans ce remplissage, un creux d'une semaine se
     * dessinerait comme une simple barre manquante, ce que l'œil lit comme
     * un jour serré et non comme un arrêt.
     *
     * @return Collection<int, array{libelle:string, valeur:int}>
     */
    public function tendanceInscriptions(): Collection
    {
        $parMois = $this->jours > 90;

        $brut = User::query()
            ->where('role', User::ROLE_USER)
            ->where('created_at', '>=', $this->debut)
            ->selectRaw($this->expressionPeriode($parMois).' as periode, COUNT(*) as total')
            ->groupBy('periode')
            ->pluck('total', 'periode');

        $points = collect();
        $curseur = $parMois ? $this->debut->startOfMonth() : $this->debut->startOfDay();

        while ($curseur <= $this->fin) {
            $cle = $curseur->format($parMois ? 'Y-m' : 'Y-m-d');

            $points->push([
                'libelle' => $parMois ? $curseur->translatedFormat('M') : $curseur->translatedFormat('d/m'),
                'valeur' => (int) ($brut[$cle] ?? 0),
            ]);

            $curseur = $parMois ? $curseur->addMonth() : $curseur->addDay();
        }

        return $points;
    }

    // =======================================================================
    // MOYENS DE PAIEMENT
    // =======================================================================

    /**
     * Répartition des paiements réussis, en pourcentage du volume.
     *
     * Les trois moyens sont TOUJOURS présents, même à zéro. Un opérateur qui
     * disparaît de la liste se lit comme un oubli d'affichage, pas comme une
     * absence de transactions.
     *
     * @return Collection<int, array{cle:string, libelle:string, total:int, part:float}>
     */
    public function moyensDePaiement(): Collection
    {
        $brut = Payment::query()
            ->successful()
            ->where('created_at', '>=', $this->debut)
            ->selectRaw('method, COUNT(*) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $somme = max(1, (int) $brut->sum());

        return collect(Payment::METHODS)->map(fn (string $libelle, string $cle) => [
            'cle' => $cle,
            'libelle' => $libelle,
            'total' => (int) ($brut[$cle] ?? 0),
            'part' => round(((int) ($brut[$cle] ?? 0)) / $somme * 100),
        ])->values();
    }

    // =======================================================================
    // LES DEUX LISTES DU BAS
    // =======================================================================

    /** @return Collection<int, User> */
    public function dernieresInscriptions(int $limite = 4): Collection
    {
        return User::query()
            ->where('role', User::ROLE_USER)
            ->latest('created_at')
            ->limit($limite)
            ->get(['id', 'name', 'email', 'created_at']);
    }

    /** @return Collection<int, Payment> */
    public function derniersPaiements(int $limite = 4): Collection
    {
        return Payment::query()
            ->with('user:id,name,email')   // sans quoi, une requête par ligne
            ->latest('created_at')
            ->limit($limite)
            ->get();
    }

    // =======================================================================
    // ALERTE CONTEXTUELLE
    // =======================================================================

    /**
     * L'encadré de droite. Il ne s'affiche QUE s'il a quelque chose à dire.
     *
     * Un bandeau permanent « tout va bien » se lit au bout de trois jours
     * comme un élément de décor, et le jour où il change vraiment, personne
     * ne le voit.
     *
     * @return array{ton:string, titre:string, texte:string, action:?string, libelle:?string}|null
     */
    public function alerte(): ?array
    {
        $enAttente = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        if ($enAttente > 0) {
            return [
                'ton' => 'attention',
                'titre' => 'Paiements à vérifier',
                'texte' => $enAttente.' paiement'.($enAttente > 1 ? 's sont' : ' est')
                    .' en attente depuis plus de 24 heures. Une vérification manuelle peut débloquer '
                    .($enAttente > 1 ? 'ces clients' : 'ce client').'.',
                'action' => route('admin.payments.index', ['statut' => Payment::STATUS_PENDING]),
                'libelle' => __('admin.raccourcis.paiements'),
            ];
        }

        $expirent = Subscription::query()->active()->expiringInDays(7)->count();

        if ($expirent > 0) {
            return [
                'ton' => 'info',
                'titre' => 'Abonnements arrivant à échéance',
                'texte' => $expirent.' abonnement'.($expirent > 1 ? 's arrivent' : ' arrive')
                    .' à échéance dans les 7 prochains jours.',
                'action' => route('admin.clients.index'),
                'libelle' => __('admin.raccourcis.clients'),
            ];
        }

        return null;
    }

    /**
     * Regroupement par jour ou par mois, dans le dialecte du moteur courant.
     *
     * SQLite ne connaît pas DATE_FORMAT et MySQL ne connaît pas strftime. Les
     * tests tournent sur SQLite en mémoire, la production sur MySQL : écrire
     * l'une des deux syntaxes en dur donnerait soit un écran qui plante en
     * production, soit une agrégation jamais couverte par les tests.
     *
     * DATE() existe des deux côtés, d'où le cas simple traité à part.
     */
    private function expressionPeriode(bool $parMois): string
    {
        if (! $parMois) {
            return 'DATE(created_at)';
        }

        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }
}
