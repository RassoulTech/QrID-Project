<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use App\Support\AdminActionType;
use App\Support\CsvExport;
use App\Support\FiltrePeriode;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Journal d'audit — lecture seule, et rien d'autre.
 *
 * Il n'existe ici ni création, ni modification, ni suppression, et il ne doit
 * pas en exister : un journal que l'administration peut retoucher ne prouve
 * rien du tout. Les écritures se font exclusivement par AdminAction::log(),
 * depuis les services qui exécutent l'action.
 *
 * LA CIBLE EST RÉSOLUE PAR LOT. Chaque ligne pointe un modèle différent —
 * un compte, un profil, un paiement, une formule — par couple
 * (target_type, target_id). Résoudre ligne par ligne donnerait trente
 * requêtes par page ; on regroupe par type et on charge en une requête par
 * type présent à l'écran.
 *
 * COMPTE DES REQUÊTES — 4 à 7 selon les types de cibles affichés :
 * comptage de pagination, lignes avec auteurs, liste des administrateurs du
 * filtre, puis une requête par type de cible présent sur la page.
 */
class AuditController extends Controller
{
    private const PAR_PAGE = 20;

    public function index(Request $request): View
    {
        $entrees = $this->requete($request)->paginate(self::PAR_PAGE)->withQueryString();

        return view('admin.audit.index', [
            'entrees' => $entrees,
            'cibles' => $this->resoudreLesCibles($entrees->getCollection()),
            'nomDeCible' => fn ($cible) => self::nommer($cible),
            'administrateurs' => User::query()->admins()->orderBy('name')->get(['id', 'name']),
            'typesAction' => AdminActionType::libelles(),
            'recherche' => $request->query('q'),
            'periode' => FiltrePeriode::valide($request->query('periode')),
            'periodes' => FiltrePeriode::options(),
            'admin' => $request->query('admin'),
            'type' => $request->query('type'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return CsvExport::stream(
            CsvExport::nom('journal-audit'),
            ['Date et heure', 'Administrateur', 'Action', 'Type de cible', 'Identifiant cible', 'Motif'],
            $this->requete($request)->reorder()->orderBy('id'),
            fn (AdminAction $e) => [
                $e->created_at?->format('d/m/Y H:i') ?? '',
                $e->admin?->name ?? 'Compte supprimé',
                AdminActionType::libelle($e->action),
                $e->target_type ? class_basename($e->target_type) : '',
                $e->target_id ?? '',
                $e->reason ?? '',
            ]
        );
    }

    /**
     * NOM LISIBLE D'UNE CIBLE.
     *
     * « Compte #42 » n'apprend rien à qui relit le journal six mois plus tard.
     * Chaque type a le champ qui l'identifie vraiment : le nom pour un compte,
     * le nom complet pour un profil, la référence pour un paiement.
     *
     * Le repli sur la clé primaire n'est pas décoratif : un modèle ajouté plus
     * tard, ou une ligne dont le champ attendu est vide, ne doit pas faire
     * tomber tout l'écran pour une seule cellule.
     */
    public static function nommer(mixed $cible): string
    {
        return match (true) {
            $cible instanceof User => $cible->name,
            $cible instanceof Profile => $cible->full_name,
            $cible instanceof Payment => $cible->provider_ref ?? 'PAY-'.$cible->id,
            $cible instanceof Plan,
            $cible instanceof Template => $cible->name,
            $cible instanceof Subscription => 'Abonnement '.($cible->plan?->name ?? '#'.$cible->id),
            default => '#'.($cible?->getKey() ?? '—'),
        };
    }

    private function requete(Request $request): Builder
    {
        return AdminAction::query()
            ->with('admin:id,name')
            ->when($request->query('q'), fn (Builder $q, string $terme) => $q->where(
                fn (Builder $c) => $c->where('reason', 'like', "%{$terme}%")
                    ->orWhere('action', 'like', "%{$terme}%")
                    ->orWhereHas('admin', fn (Builder $a) => $a->where('name', 'like', "%{$terme}%"))
            ))
            ->when($request->query('admin'), fn (Builder $q, string $id) => $q->where('admin_id', $id))
            ->when($request->query('type'), fn (Builder $q, string $type) => $q->where('action', $type))
            ->tap(fn (Builder $q) => FiltrePeriode::appliquer(
                $q, $request->query('periode'), 'admin_actions.created_at'
            ))
            ->latest('created_at');
    }

    /**
     * Résout les cibles de la page en une requête par type.
     *
     * @param  Collection<int, AdminAction>  $entrees
     * @return array<string, Collection> indexé par target_type
     */
    private function resoudreLesCibles($entrees): array
    {
        $cibles = [];

        foreach ($entrees->whereNotNull('target_type')->groupBy('target_type') as $type => $lignes) {
            // Le type vient de la base, pas de la requête HTTP — mais une
            // classe supprimée entre-temps ferait tomber l'écran entier pour
            // une seule vieille ligne de journal.
            if (! class_exists($type)) {
                continue;
            }

            $cibles[$type] = $type::query()
                ->whereIn('id', $lignes->pluck('target_id')->filter()->unique())
                ->get()
                ->keyBy('id');
        }

        return $cibles;
    }
}
