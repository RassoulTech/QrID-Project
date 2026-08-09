<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\Template;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des profils.
 *
 * LA COLONNE « VUES » EST LE PIÈGE DE CET ÉCRAN. Compter les événements par
 * profil dans la boucle d'affichage donnerait une requête par ligne — quinze
 * requêtes de plus par page, invisibles en développement avec cinq profils,
 * fatales avec trois mille. `withCount` fait le comptage en sous-requête, dans
 * la requête principale.
 *
 * L'ADMINISTRATION NE MODIFIE JAMAIS UN PROFIL. Il n'y a ici ni `edit` ni
 * `update` : la seule prise est la désactivation avec motif, portée par
 * ProfileDeactivationController.
 *
 * COMPTE DES REQUÊTES — 3 : modèles du filtre, comptage de pagination,
 * lignes avec propriétaire, modèle et compteur de vues.
 */
class ProfileController extends Controller
{
    private const PAR_PAGE = 15;

    public function index(Request $request): View
    {
        $profils = $this->requete($request)->paginate(self::PAR_PAGE)->withQueryString();

        return view('admin.profiles.index', [
            'profils' => $profils,
            'modeles' => Template::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'recherche' => $request->query('q'),
            'etat' => $request->query('etat'),
            'modele' => $request->query('modele'),
            'etats' => [
                Profile::ETAT_PUBLIE => 'Publié',
                Profile::ETAT_BROUILLON => 'Brouillon',
                Profile::ETAT_DESACTIVE => 'Désactivé',
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return CsvExport::stream(
            CsvExport::nom('profils'),
            ['Nom complet', 'Slug', 'Modèle', 'État', 'Vues', 'Publication', 'Motif de désactivation'],
            $this->requete($request)->reorder()->orderBy('profiles.id'),
            fn (Profile $p) => [
                $p->full_name,
                $p->slug,
                $p->template?->name ?? '',
                $p->etatLibelle(),
                $p->vues_count ?? 0,
                $p->is_active ? ($p->updated_at?->format('d/m/Y') ?? '') : '',
                $p->deactivated_reason ?? '',
            ]
        );
    }

    private function requete(Request $request): Builder
    {
        return Profile::query()
            ->with(['user:id,name,email', 'template:id,name'])
            // Sous-requête, pas une requête par ligne.
            ->withCount(['events as vues_count' => fn (Builder $q) => $q->where('type', ProfileEvent::TYPE_VIEW)])
            ->when($request->query('q'), fn (Builder $q, string $terme) => $q->where(
                fn (Builder $c) => $c->where('first_name', 'like', "%{$terme}%")
                    ->orWhere('last_name', 'like', "%{$terme}%")
                    ->orWhere('slug', 'like', "%{$terme}%")
            ))
            ->inState($request->query('etat'))
            ->when($request->query('modele'), fn (Builder $q, string $slug) => $q->whereHas(
                'template', fn (Builder $t) => $t->where('slug', $slug)
            ))
            ->latest('profiles.created_at');
    }
}
