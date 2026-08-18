{{--
  JOURNAL D'AUDIT — écran 8.

  LECTURE SEULE, et il n'existe aucune route d'écriture ni de suppression :
  un journal que l'administration peut retoucher ne prouve rien.

  Les cibles sont résolues PAR LOT dans le contrôleur, une requête par type
  présent à l'écran. Les résoudre ligne par ligne donnerait vingt requêtes
  par page.
--}}
@php use App\Support\AdminActionType; @endphp

<x-admin-layout
    title="Journal d'audit"
    subtitle="Traçabilité complète des actions administratives sensibles."
>
    <x-slot:actions>
        <a href="{{ route('admin.audit.export', request()->query()) }}" class="adm-btn adm-btn--clair">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            Exporter CSV
        </a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.audit.index') }}" class="adm-filtres">
        <div class="adm-filtre adm-filtre--large adm-filtres__recherche">
            <label for="q">Recherche</label>
            <svg class="adm-filtres__loupe" width="14" height="14" viewBox="0 0 16 16"
                 fill="currentColor" aria-hidden="true">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
            </svg>
            <input type="search" id="q" name="q" class="adm-filtres__champ"
                   value="{{ $recherche }}" placeholder="Action, cible ou motif…">
        </div>

        <div class="adm-filtre">
            <label for="periode">Période</label>
            <select id="periode" name="periode" class="adm-select">
                @foreach ($periodes as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($periode === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filtre">
            <label for="admin">Administrateur</label>
            <select id="admin" name="admin" class="adm-select">
                <option value="">Tous les administrateurs</option>
                @foreach ($administrateurs as $a)
                    <option value="{{ $a->id }}" @selected((string) $admin === (string) $a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filtre">
            <label for="type">Type d'action</label>
            <select id="type" name="type" class="adm-select">
                <option value="">Tous les types</option>
                @foreach ($typesAction as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($type === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="adm-btn adm-btn--vert">Filtrer</button>

        @if ($recherche || $periode || $admin || $type)
            <a href="{{ route('admin.audit.index') }}" class="adm-btn adm-btn--clair">Réinitialiser</a>
        @endif
    </form>

    <div class="adm-bloc">
        {{-- Compteur et etat vide : un seul composant pour les deux, voir
             x-liste-resultats. --}}
        <x-liste-resultats :total="$entrees->total()"
                           :filtre="(bool) ($recherche || $type || $admin || $periode)"
                           :reset="route('admin.audit.index')"
                           nom="entrée" icon="document"
                           vide="Les actions d'administration seront consignées ici." />

        @if (! $entrees->isEmpty())

            <div class="table-scroll">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Date et heure</th>
                            <th scope="col">Administrateur</th>
                            <th scope="col">Action</th>
                            <th scope="col">Cible</th>
                            <th scope="col">Motif</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($entrees as $entree)
                            @php
                                $cible = $entree->target_type
                                    ? ($cibles[$entree->target_type][$entree->target_id] ?? null)
                                    : null;
                            @endphp

                            <tr>
                                <td>
                                    <span class="adm-cell-id__texte">
                                        <span class="adm-table__principal">
                                            {{ $entree->created_at?->format('d/m/Y') ?? '—' }}
                                        </span>
                                        <span class="adm-table__second">
                                            {{ $entree->created_at?->format('H:i:s') ?? '' }}
                                        </span>
                                    </span>
                                </td>

                                <td>
                                    <span class="adm-cell-id">
                                        <span class="adm-avatar adm-avatar--sm" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($entree->admin?->name ?? '?', 0, 2)) }}
                                        </span>
                                        {{-- Le journal survit à la suppression de son
                                             auteur : c'est le principe même d'un audit. --}}
                                        <span>{{ $entree->admin?->name ?? 'Compte supprimé' }}</span>
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $ton = match (AdminActionType::ton($entree->action)) {
                                            'danger' => 'danger',
                                            'attention' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <x-badge :variant="$ton">{{ AdminActionType::libelle($entree->action) }}</x-badge>
                                </td>

                                <td>
                                    @if ($cible === null)
                                        <span class="adm-table__second">—</span>
                                    @else
                                        <span class="adm-cell-id__texte">
                                            <span class="adm-table__principal">{{ $nomDeCible($cible) }}</span>
                                            <span class="adm-table__second">
                                                {{ class_basename($entree->target_type) }} #{{ $entree->target_id }}
                                            </span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Le motif n'est PAS tronqué. C'est la seule colonne
                                     qui explique pourquoi l'action a eu lieu ; la couper
                                     à quarante caractères viderait le journal de son
                                     objet. La cellule s'élargit, le conteneur défile. --}}
                                <td class="adm-table__second" style="min-width:260px;white-space:normal;">
                                    {{ $entree->reason ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="adm-pied">
                <p class="adm-pied__compte">
                    Affichage {{ $entrees->firstItem() }} à {{ $entrees->lastItem() }}
                    sur {{ number_format($entrees->total(), 0, ',', ' ') }} entrée{{ $entrees->total() > 1 ? 's' : '' }}
                </p>
                {{ $entrees->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
