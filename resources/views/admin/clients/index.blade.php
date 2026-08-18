{{--
  LISTE DES CLIENTS — écran 2.

  FILTRES EN GET, dans l'URL. La liste filtrée se partage, se met en signet,
  et le bouton « précédent » du navigateur retrouve exactement ce qu'on
  regardait. Un filtre mémorisé en session produirait l'incident inverse : on
  revient deux jours plus tard, la liste est amputée, et rien ne dit pourquoi.

  Les données viennent de ClientController : `users` de rôle client, avec
  leur profil et leurs abonnements chargés d'avance.
--}}
<x-admin-layout
    title="Liste des clients"
    subtitle="Gérer et consulter la totalité des utilisateurs inscrits."
>
    <x-slot:actions>
        <a href="{{ route('admin.clients.export', request()->query()) }}" class="adm-btn adm-btn--clair">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            Exporter CSV
        </a>
    </x-slot:actions>

    {{-- ==================== FILTRES ==================== --}}
    <form method="GET" action="{{ route('admin.clients.index') }}" class="adm-filtres" data-auto-filtre>
        <div class="adm-filtre adm-filtre--large adm-filtres__recherche">
            <label for="q">Recherche</label>
            <svg class="adm-filtres__loupe" width="14" height="14" viewBox="0 0 16 16"
                 fill="currentColor" aria-hidden="true">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
            </svg>
            <input type="search" id="q" name="q" class="adm-filtres__champ"
                   value="{{ $recherche }}" placeholder="Nom, e-mail ou téléphone…">
        </div>

        {{-- UNE PÉRIODE, PAS UNE DATE DE DÉBUT. « Inscrit depuis le … » seul
             ne filtre presque rien : on tape une date et on obtient encore
             tout ce qui a suivi. La question posée est « qui s'est inscrit
             ce mois-ci », et elle tient en un choix. --}}
        <div class="adm-filtre">
            <label for="periode">Inscription</label>
            <select id="periode" name="periode" class="adm-select">
                @foreach ($periodes as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($periode === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filtre">
            <label for="statut">Statut du compte</label>
            <select id="statut" name="statut" class="adm-select">
                <option value="">Tous les statuts</option>
                <option value="actif" @selected($statut === 'actif')>Compte actif</option>
                <option value="bloque" @selected($statut === 'bloque')>Compte bloqué</option>
                <option value="abonne" @selected($statut === 'abonne')>Avec abonnement</option>
                <option value="sans_abonnement" @selected($statut === 'sans_abonnement')>Sans abonnement</option>
            </select>
        </div>

        <button type="submit" class="adm-btn adm-btn--vert" data-auto-filtre-bouton>Filtrer</button>

        {{-- N'apparaît que s'il y a quelque chose à effacer : un bouton
             « Réinitialiser » sur une liste vierge n'a aucun sens. --}}
        @if ($recherche || $statut || $periode)
            <a href="{{ route('admin.clients.index') }}" class="adm-btn adm-btn--clair">Réinitialiser</a>
        @endif
    </form>

    {{-- ==================== TABLEAU ==================== --}}
    <div class="adm-bloc">
        {{-- Compteur et etat vide : un seul composant pour les deux, voir
             x-liste-resultats. --}}
        <x-liste-resultats :total="$clients->total()"
                           :filtre="(bool) ($recherche || $statut || $periode)"
                           :reset="route('admin.clients.index')"
                           nom="client" icon="profile"
                           vide="Les comptes clients apparaîtront ici dès la première inscription." />

        @if (! $clients->isEmpty())

            <div class="table-scroll">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Client</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Téléphone</th>
                            <th scope="col">Profil</th>
                            <th scope="col">Abonnement</th>
                            <th scope="col">Inscription</th>
                            <th scope="col" class="adm-table__actions">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($clients as $client)
                            @php
                                $profil = $client->profile;
                                $abo = $client->activeSubscription();
                            @endphp

                            <tr>
                                <td>
                                    <span class="adm-cell-id">
                                        <span class="adm-avatar adm-avatar--sm" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}
                                        </span>
                                        <span class="adm-cell-id__texte">
                                            <span class="adm-table__principal">{{ $client->name }}</span>
                                            @if ($client->isBlocked())
                                                <span class="adm-table__second">Compte bloqué</span>
                                            @endif
                                        </span>
                                    </span>
                                </td>

                                <td class="adm-table__second">{{ $client->email }}</td>
                                <td class="adm-table__second">{{ $client->phone ?? '—' }}</td>

                                <td>
                                    {{-- Trois états distincts, pas deux : un brouillon
                                         jamais publié n'est pas un profil coupé par
                                         l'administration. --}}
                                    @if ($profil === null)
                                        <x-badge variant="secondary">Aucun profil</x-badge>
                                    @elseif ($profil->isDeactivated())
                                        <x-badge variant="danger">Désactivé</x-badge>
                                    @elseif ($profil->is_active)
                                        <x-badge variant="success">Publié</x-badge>
                                    @else
                                        <x-badge variant="secondary">Brouillon</x-badge>
                                    @endif
                                </td>

                                <td>
                                    @if ($abo === null)
                                        <x-badge variant="secondary">Aucun</x-badge>
                                    @elseif ($abo->isTrial())
                                        <x-badge variant="warning">Essai gratuit</x-badge>
                                    @else
                                        <x-badge variant="success">{{ $abo->plan?->name ?? 'Actif' }}</x-badge>
                                    @endif
                                </td>

                                <td class="adm-table__second">
                                    {{ $client->created_at?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td class="adm-table__actions">
                                    <a href="{{ route('admin.clients.show', $client) }}"
                                       class="adm-lien">Consulter</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="adm-pied">
                {{-- Le compteur porte le TOTAL du filtre, pas de la page.
                     « 15 clients » sur une liste qui en compte 310 ferait
                     prendre des décisions sur un nombre faux. --}}
                <p class="adm-pied__compte">
                    Affichage {{ $clients->firstItem() }} à {{ $clients->lastItem() }}
                    sur {{ number_format($total, 0, ',', ' ') }}
                    client{{ $total > 1 ? 's' : '' }}
                </p>

                {{ $clients->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
