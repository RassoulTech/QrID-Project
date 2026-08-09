<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\CsvExport;
use App\Support\FiltrePeriode;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des paiements.
 *
 * LES COMPTEURS D'ONGLETS SONT CALCULÉS EN UNE REQUÊTE, pas en trois. Trois
 * `count()` séparés donneraient le même résultat pour trois fois le coût, et
 * surtout trois photographies prises à trois instants différents : sur une
 * table active, la somme des onglets ne correspondrait pas au total affiché.
 *
 * LA LIGNE DE TOTAL porte le montant des paiements RÉUSSIS du filtre courant,
 * pas de la page affichée. Un total de page se lirait comme un total tout
 * court et fausserait toute lecture au-delà des quinze premières lignes.
 *
 * COMPTE DES REQUÊTES — 4 : compteurs d'onglets, comptage de pagination,
 * lignes avec leurs relations, total du filtre.
 */
class PaymentController extends Controller
{
    private const PAR_PAGE = 15;

    public function index(Request $request): View
    {
        $requete = $this->requete($request);

        $paiements = $requete->clone()->paginate(self::PAR_PAGE)->withQueryString();

        return view('admin.payments.index', [
            'paiements' => $paiements,
            'compteurs' => $this->compteurs(),
            'total' => (int) $this->requete($request, sansStatut: false)
                ->clone()->where('payments.status', Payment::STATUS_SUCCESS)->sum('amount_fcfa'),
            'statut' => $request->query('statut'),
            'moyen' => $request->query('moyen'),
            'periode' => FiltrePeriode::valide($request->query('periode')),
            'periodes' => FiltrePeriode::options(),
            'methodes' => Payment::METHODS,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return CsvExport::stream(
            CsvExport::nom('paiements'),
            ['Référence', 'Date', 'Client', 'Formule', 'Moyen', 'Montant FCFA', 'Statut'],
            $this->requete($request)->orderBy('payments.id'),
            fn (Payment $p) => [
                $p->provider_ref ?? ('PAY-'.$p->id),
                $p->created_at?->format('d/m/Y H:i') ?? '',
                $p->user?->name ?? 'Compte supprimé',
                $p->subscription?->plan?->name ?? ($p->payload['plan_slug'] ?? ''),
                $p->method_label,
                $p->amount_fcfa,
                self::statutLibelle($p->status),
            ]
        );
    }

    /**
     * Un seul balayage, trois compteurs.
     *
     * `SUM(condition)` fonctionne sur MySQL comme sur SQLite : la comparaison
     * y rend 1 ou 0. C'est déjà la forme employée par le tableau de bord
     * client, on ne réinvente pas une seconde manière de compter.
     *
     * @return array<string, int>
     */
    private function compteurs(): array
    {
        $ligne = Payment::query()
            ->selectRaw('COUNT(*) as tous')
            ->selectRaw('SUM(status = ?) as reussis', [Payment::STATUS_SUCCESS])
            ->selectRaw('SUM(status = ?) as attente', [Payment::STATUS_PENDING])
            ->selectRaw('SUM(status = ?) as echoues', [Payment::STATUS_FAILED])
            ->first();

        return [
            'tous' => (int) ($ligne->tous ?? 0),
            Payment::STATUS_SUCCESS => (int) ($ligne->reussis ?? 0),
            Payment::STATUS_PENDING => (int) ($ligne->attente ?? 0),
            Payment::STATUS_FAILED => (int) ($ligne->echoues ?? 0),
        ];
    }

    private function requete(Request $request, bool $sansStatut = true): Builder
    {
        return Payment::query()
            ->with(['user:id,name,email', 'subscription.plan:id,name'])
            ->when($sansStatut && $request->query('statut'), fn (Builder $q, string $s) => $q->where('payments.status', $s))
            ->when($request->query('moyen'), fn (Builder $q, string $m) => $q->where('method', $m))
            ->tap(fn (Builder $q) => FiltrePeriode::appliquer(
                $q, $request->query('periode'), 'payments.created_at'
            ))
            ->latest('payments.created_at');
    }

    public static function statutLibelle(string $statut): string
    {
        return match ($statut) {
            Payment::STATUS_SUCCESS => 'Réussi',
            Payment::STATUS_PENDING => 'En attente',
            default => 'Échoué',
        };
    }
}
