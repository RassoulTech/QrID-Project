<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * LISTE DES ABONNEMENTS.
 *
 * CET ÉCRAN N'A PAS DE MAQUETTE. Le menu validé porte l'entrée, les huit
 * maquettes fournies n'en montrent aucune. Il est donc construit sur la
 * grammaire des autres listes — mêmes filtres, même tableau, même pied — pour
 * qu'il ne détonne pas le jour où la maquette arrivera, et qu'il soit simple
 * à remplacer si elle diffère.
 *
 * CE QU'IL APPORTE que la liste des clients ne donne pas : les échéances. On
 * ouvre cet écran pour savoir qui arrive à terme cette semaine, pas pour
 * chercher une personne.
 *
 * COMPTE DES REQUÊTES — 4 : compteurs d'onglets, comptage de pagination,
 * lignes avec compte et formule, total des échéances proches.
 */
class SubscriptionController extends Controller
{
    private const PAR_PAGE = 20;

    public function index(Request $request): View
    {
        $abonnements = $this->requete($request)->paginate(self::PAR_PAGE)->withQueryString();

        return view('admin.subscriptions.index', [
            'abonnements' => $abonnements,
            'compteurs' => $this->compteurs(),
            'plans' => Plan::query()->orderBy('price_fcfa')->get(['id', 'name', 'slug']),
            'statut' => $request->query('statut'),
            'plan' => $request->query('plan'),
            'echeance' => $request->query('echeance'),
            'statuts' => [
                Subscription::STATUS_ACTIVE => 'Actifs',
                Subscription::STATUS_EXPIRED => 'Expirés',
                Subscription::STATUS_CANCELLED => 'Annulés',
                Subscription::STATUS_PENDING => 'En attente',
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return CsvExport::stream(
            CsvExport::nom('abonnements'),
            ['Client', 'E-mail', 'Formule', 'Prix FCFA', 'Début', 'Échéance', 'Jours restants', 'Statut'],
            $this->requete($request)->reorder()->orderBy('subscriptions.id'),
            fn (Subscription $a) => [
                $a->user?->name ?? 'Compte supprimé',
                $a->user?->email ?? '',
                $a->plan?->name ?? '',
                $a->plan?->price_fcfa ?? 0,
                $a->starts_at?->format('d/m/Y') ?? '',
                $a->ends_at?->format('d/m/Y') ?? 'sans terme',
                $a->daysRemaining() ?? '',
                self::statutLibelle($a->status),
            ]
        );
    }

    /**
     * Un seul balayage, quatre compteurs — comme sur l'écran des paiements.
     *
     * @return array<string, int>
     */
    private function compteurs(): array
    {
        $ligne = Subscription::query()
            ->selectRaw('COUNT(*) as tous')
            ->selectRaw('SUM(status = ?) as actifs', [Subscription::STATUS_ACTIVE])
            ->selectRaw('SUM(status = ?) as expires', [Subscription::STATUS_EXPIRED])
            ->selectRaw('SUM(status = ?) as annules', [Subscription::STATUS_CANCELLED])
            ->first();

        return [
            'tous' => (int) ($ligne->tous ?? 0),
            Subscription::STATUS_ACTIVE => (int) ($ligne->actifs ?? 0),
            Subscription::STATUS_EXPIRED => (int) ($ligne->expires ?? 0),
            Subscription::STATUS_CANCELLED => (int) ($ligne->annules ?? 0),
        ];
    }

    private function requete(Request $request): Builder
    {
        return Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name,price_fcfa,duration_days'])
            ->when($request->query('statut'), fn (Builder $q, string $s) => $q->where('subscriptions.status', $s))
            ->when($request->query('plan'), fn (Builder $q, string $slug) => $q->whereHas(
                'plan', fn (Builder $p) => $p->where('slug', $slug)
            ))
            // Le filtre d'échéance est la raison d'être de l'écran : repérer ce
            // qui arrive à terme avant que le client ne s'en aperçoive.
            ->when($request->query('echeance'), fn (Builder $q, string $jours) => $q
                ->where('subscriptions.status', Subscription::STATUS_ACTIVE)
                ->whereNotNull('ends_at')
                ->whereBetween('ends_at', [now(), now()->addDays(max(1, (int) $jours))])
            )
            // Par échéance croissante : le plus urgent en tête, ce qu'aucun
            // classement par date de création ne donnerait.
            ->orderByRaw('ends_at IS NULL, ends_at ASC');
    }

    public static function statutLibelle(string $statut): string
    {
        return match ($statut) {
            Subscription::STATUS_ACTIVE => 'Actif',
            Subscription::STATUS_EXPIRED => 'Expiré',
            Subscription::STATUS_CANCELLED => 'Annulé',
            default => 'En attente',
        };
    }
}
