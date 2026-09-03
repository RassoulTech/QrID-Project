<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Services\ProfileWizardService;
use App\Services\QrCodeService;
use App\Services\StatistiquesLecture;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tableau de bord — trois colonnes, aucune donnée inventée.
 *
 * TOUT est préparé ici. Une vue ne compte pas, ne calcule pas, n'interroge
 * pas la base : elle affiche ce qu'on lui donne. C'est ce qui permet de
 * connaître le nombre exact de requêtes de la page.
 *
 * Les agrégats passent par UNE requête SQL chacun, jamais par une boucle PHP
 * sur des milliers de lignes d'événements.
 *
 * RÈGLE D'AFFICHAGE : aucune carte ne montre un zéro. Quand une donnée est
 * absente, on renvoie null et la vue affiche un état d'attente explicite —
 * « 0 vue » décourage, « partagez votre carte pour voir arriver vos premières
 * vues » indique quoi faire.
 */
class DashboardController extends Controller
{
    /** Fenêtres proposées par le sélecteur de période de l'histogramme. */
    private const PERIODES = [7, 30];

    public function __construct(
        private ProfileWizardService $wizard,
        private QrCodeService $qr,
        private StatistiquesLecture $lecture,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $profile = $user->profile()->with('socialLinks')->first();

        if (! $profile) {
            return view('dashboard.empty', [
                'user' => $user,
                'resumeStep' => $this->wizard->isInProgress() ? $this->wizard->nextStep() : null,
            ]);
        }

        $abonnement = $user->activeSubscription();

        // Période de l'histogramme : 7 ou 30 jours, jamais une valeur libre
        // venue de l'URL.
        $jours = (int) $request->query('periode', 7);
        $jours = in_array($jours, self::PERIODES, true) ? $jours : 7;

        return view('dashboard.active', [
            'profile' => $profile,
            'subscription' => $abonnement,

            'expired' => $abonnement === null
                ? $user->subscriptions()->with('plan')->latest('ends_at')->first()
                : null,

            'stats' => $this->stats($profile->id, $abonnement?->daysRemaining()),
            'serie' => $this->serie($profile->id, $jours),
            'periode' => $jours,
            'periodes' => self::PERIODES,

            'visiteurs' => $this->derniersVisiteurs($profile->id),
            'journal' => $this->journalDuCompte($user->id, $profile),

            'publicUrl' => route('profile.public', $profile->slug),
            'qrSvg' => $this->qr->svg($profile),
        ]);
    }

    // -----------------------------------------------------------------------

    /**
     * Les quatre chiffres de la vue d'ensemble, en UNE requête agrégée.
     *
     * Quatre count() séparés, c'étaient quatre allers-retours pour quatre
     * nombres lus sur la même table. Un seul SELECT avec des agrégats
     * conditionnels suffit.
     *
     * Chaque valeur vaut null quand il n'y a rien à montrer : la carte
     * affiche alors son état d'attente au lieu d'un zéro.
     *
     * @return array{views:?int, scans:?int, saves:?int, days:?int}
     */
    private function stats(int $profileId, ?int $joursRestants): array
    {
        /*
         | CES CHIFFRES SONT CUMULÉS, ET C'ÉTAIT UN BALAYAGE SANS BORNE.
         |
         | La requête sommait TOUTE la table d'événements du profil, sans
         | aucune limite de date. Instantané sur un profil ouvert la semaine
         | dernière ; sur un profil très consulté depuis deux ans, un
         | balayage qui grossit indéfiniment — et c'est la page la plus
         | ouverte de l'espace client.
         |
         | Le service lit les agrégats jusqu'au dernier jour traité et ne
         | relit la source qu'au-delà : le coût cesse de dépendre de l'âge
         | du profil.
         */
        $cumules = $this->lecture->totauxCumules($profileId);

        // null et non zéro : la tuile affiche alors son état d'attente —
        // « partagez votre carte » — au lieu d'un « 0 » qui décourage.
        $valeur = fn (int $n) => $n > 0 ? $n : null;

        return [
            'views' => $valeur($cumules['vues']),
            'scans' => $valeur($cumules['scans']),
            'saves' => $valeur($cumules['saves']),
            'days' => $joursRestants,
        ];
    }

    /**
     * Histogramme des vues, agrégé en SQL puis complété en PHP.
     *
     * groupBy sur la date ne renvoie QUE les jours ayant au moins un
     * événement : un histogramme construit dessus tasserait les barres et
     * mentirait sur les creux. On repart donc d'une série de jours pleine et
     * on y verse les comptes obtenus — un seul aller-retour, aucune boucle de
     * requêtes.
     *
     * @return list<array{jour:string, libelle:string, total:int}>|null
     */
    private function serie(int $profileId, int $jours): ?array
    {
        /*
         | LA SÉRIE VIENT DU SERVICE, comme celle de l'écran Statistiques et
         | celle de l'administration. C'était la TROISIÈME copie de la même
         | requête, groupée sur `DATE(created_at)` — la forme que ce service
         | existe précisément pour supprimer.
         |
         | Le tableau de bord ne trace que les VUES : c'est la seule courbe
         | qui répond à « est-ce que ma carte circule ? ». Les scans et les
         | enregistrements ont leur écran dédié.
         */
        $points = $this->lecture->serieDetaillee(
            Carbon::today()->subDays($jours - 1), $jours, $profileId,
        );

        if ($points->sum('vues') === 0) {
            return null;   // aucun graphique plat : la vue invite à partager
        }

        return $points->map(fn (array $jour, string $date) => [
            'jour' => $date,
            'libelle' => Carbon::parse($date)->translatedFormat($jours <= 7 ? 'D' : 'j/m'),
            'total' => $jour['vues'],
        ])->values()->all();
    }

    /**
     * Dernières consultations.
     *
     * SANS VILLE, et c'est délibéré : profile_events ne stocke que
     * sha256(ip + clé), jamais l'adresse en clair. Aucune géolocalisation
     * n'est donc dérivable — il faudrait conserver les IP, ce que ce produit
     * a choisi de ne pas faire. Restent le type d'accès et l'horodatage.
     *
     * @return Collection<int, ProfileEvent>
     */
    private function derniersVisiteurs(int $profileId)
    {
        return ProfileEvent::query()
            ->where('profile_id', $profileId)
            ->whereIn('type', [ProfileEvent::TYPE_VIEW, ProfileEvent::TYPE_SCAN])
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'type', 'created_at']);
    }

    /**
     * Journal du compte — des faits, avec leur date réelle.
     *
     * Les sources sont hétérogènes (paiements, profil, notifications) : on les
     * lit séparément, en trois requêtes bornées, puis on trie en PHP sur une
     * poignée de lignes. Une union SQL sur trois tables aux colonnes
     * différentes coûterait plus cher à écrire qu'à exécuter.
     *
     * @return list<array{titre:string, detail:?string, date:Carbon}>
     */
    private function journalDuCompte(int $userId, Profile $profile): array
    {
        $entrees = [];

        foreach (Payment::where('user_id', $userId)->successful()->latest()->limit(3)->get() as $paiement) {
            $entrees[] = [
                'titre' => 'Paiement validé',
                'detail' => $paiement->formattedAmount().' · '.$paiement->method_label,
                'date' => $paiement->created_at,
            ];
        }

        $entrees[] = [
            'titre' => $profile->is_active ? 'Carte publiée' : 'Carte créée',
            'detail' => $profile->full_name,
            'date' => $profile->created_at,
        ];

        if ($profile->updated_at->gt($profile->created_at)) {
            $entrees[] = [
                'titre' => 'Carte modifiée',
                'detail' => null,
                'date' => $profile->updated_at,
            ];
        }

        foreach (Notification::where('user_id', $userId)->latest()->limit(3)->get() as $notification) {
            $entrees[] = [
                'titre' => $notification->title,
                'detail' => $notification->body,
                'date' => $notification->created_at,
            ];
        }

        usort($entrees, fn ($a, $b) => $b['date'] <=> $a['date']);

        return array_slice($entrees, 0, 6);
    }
}
