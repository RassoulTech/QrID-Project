{{--
  LISTE DES PROFILS — écran 5.

  L'ADMINISTRATION NE MODIFIE JAMAIS LE CONTENU D'UN PROFIL. Il n'y a donc
  aucune action « Modifier » sur cet écran, et ce n'est pas un oubli : la
  seule prise est la désactivation, avec motif obligatoire.

  La colonne « Vues » vient d'un withCount en sous-requête, pas d'un comptage
  par ligne — quinze requêtes de plus par page seraient invisibles avec cinq
  profils et fatales avec trois mille.
--}}
@php use App\Models\Profile; @endphp

<x-admin-layout
    title="Liste des profils"
    subtitle="Suivre et contrôler les cartes numériques publiées par les clients."
>
    <x-slot:actions>
        <a href="{{ route('admin.profiles.export', request()->query()) }}" class="adm-btn adm-btn--clair">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            Exporter CSV
        </a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.profiles.index') }}" class="adm-filtres">
        <div class="adm-filtre adm-filtre--large adm-filtres__recherche">
            <label for="q">Recherche</label>
            <svg class="adm-filtres__loupe" width="14" height="14" viewBox="0 0 16 16"
                 fill="currentColor" aria-hidden="true">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
            </svg>
            <input type="search" id="q" name="q" class="adm-filtres__champ"
                   value="{{ $recherche }}" placeholder="Nom ou identifiant public…">
        </div>

        <div class="adm-filtre">
            <label for="etat">État du profil</label>
            <select id="etat" name="etat" class="adm-select">
                <option value="">Tous les états</option>
                @foreach ($etats as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($etat === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filtre">
            <label for="modele">Modèle utilisé</label>
            <select id="modele" name="modele" class="adm-select">
                <option value="">Tous les modèles</option>
                @foreach ($modeles as $m)
                    <option value="{{ $m->slug }}" @selected($modele === $m->slug)>{{ $m->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="adm-btn adm-btn--vert">Filtrer</button>

        @if ($recherche || $etat || $modele)
            <a href="{{ route('admin.profiles.index') }}" class="adm-btn adm-btn--clair">Réinitialiser</a>
        @endif
    </form>

    <div class="adm-bloc">
        @if ($profils->isEmpty())
            <x-empty-state icon="profile"
                title="Aucun profil"
                message="Aucune carte ne correspond à ces critères." />
        @else
            <div class="table-scroll">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Profil</th>
                            <th scope="col">Identifiant public</th>
                            <th scope="col">Modèle</th>
                            <th scope="col">État</th>
                            <th scope="col">Vues</th>
                            <th scope="col">Publication</th>
                            <th scope="col" class="adm-table__actions">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($profils as $profil)
                            <tr>
                                <td>
                                    <span class="adm-cell-id">
                                        <span class="adm-avatar adm-avatar--sm" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($profil->first_name, 0, 1).mb_substr($profil->last_name, 0, 1)) }}
                                        </span>
                                        <span class="adm-cell-id__texte">
                                            <span class="adm-table__principal">{{ $profil->full_name }}</span>
                                            <span class="adm-table__second">{{ $profil->job_title ?? '—' }}</span>
                                        </span>
                                    </span>
                                </td>

                                <td class="adm-table__second">
                                    {{-- La carte publiée est consultable : c'est la seule
                                         façon de vérifier un signalement sans demander
                                         au client de faire une capture d'écran. --}}
                                    @if ($profil->is_active)
                                        <a class="adm-lien" href="{{ route('profile.public', $profil) }}"
                                           target="_blank" rel="noopener">/p/{{ $profil->slug }}</a>
                                    @else
                                        /p/{{ $profil->slug }}
                                    @endif
                                </td>

                                <td class="adm-table__second">{{ $profil->template?->name ?? '—' }}</td>

                                <td>
                                    @switch ($profil->etat())
                                        @case (Profile::ETAT_PUBLIE)
                                            <x-badge variant="success">Publié</x-badge>
                                            @break
                                        @case (Profile::ETAT_DESACTIVE)
                                            <x-badge variant="danger">Désactivé</x-badge>
                                            @break
                                        @default
                                            <x-badge variant="secondary">Brouillon</x-badge>
                                    @endswitch
                                </td>

                                {{-- Le zéro est affiché : une carte jamais vue est
                                     une information, pas une case à laisser vide. --}}
                                <td class="adm-table__principal">
                                    {{ number_format($profil->vues_count ?? 0, 0, ',', ' ') }}
                                </td>

                                <td class="adm-table__second">
                                    {{ $profil->is_active ? ($profil->updated_at?->format('d/m/Y') ?? '—') : '—' }}
                                </td>

                                <td class="adm-table__actions">
                                    @if ($profil->isDeactivated())
                                        <x-admin-action-form
                                            :action="route('admin.profiles.reactivate', $profil)"
                                            methode="DELETE"
                                            libelle="Réactiver"
                                            confirmation="Lever la désactivation"
                                            :id="'react-'.$profil->id"
                                            titre="Lever la désactivation"
                                            :texte="'Motif de la désactivation en cours : '
                                                .($profil->deactivated_reason ?? 'non renseigné')
                                                .'. Le profil restera en brouillon : c\'est à son propriétaire de le republier.'" />
                                    @else
                                        <x-admin-action-form
                                            :action="route('admin.profiles.deactivate', $profil)"
                                            libelle="Désactiver"
                                            confirmation="Désactiver ce profil"
                                            ton="danger"
                                            :id="'desact-'.$profil->id"
                                            titre="Désactiver ce profil"
                                            texte="La carte cesse immédiatement d'être accessible publiquement. Le contenu n'est ni modifié ni supprimé, et le client conserve son accès." />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="adm-pied">
                <p class="adm-pied__compte">
                    Affichage {{ $profils->firstItem() }} à {{ $profils->lastItem() }}
                    sur {{ number_format($profils->total(), 0, ',', ' ') }} profil{{ $profils->total() > 1 ? 's' : '' }}
                </p>
                {{ $profils->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
