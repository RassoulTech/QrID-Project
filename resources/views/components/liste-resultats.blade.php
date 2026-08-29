{{--
  x-liste-resultats — ce qu'une liste filtrée doit dire d'elle-même.

      <x-liste-resultats :total="$clients->total()"
                         :filtre="(bool) ($recherche || $statut || $periode)"
                         :reset="route('admin.clients.index')"
                         nom="client" icon="profile"
                         vide="Les comptes apparaîtront ici dès la première inscription." />

  ═══════════════════════════════════════════════════════════════════════
  DEUX MANQUES QU'IL COMBLE, ET POURQUOI ILS COMPTENT
  ═══════════════════════════════════════════════════════════════════════
  LE COMPTEUR. Une liste paginée n'annonçait nulle part combien d'éléments
  elle contient. On filtrait, on obtenait quinze lignes, et l'on ne savait pas
  s'il s'agissait de quinze résultats ou de la première page de deux cents.
  C'est la seule information qui dise si un filtre a mordu.

  L'ÉTAT VIDE SPÉCIFIQUE. Une liste vide APRÈS filtrage ne dit pas la même
  chose qu'une liste vide tout court. « Aucun paiement pour l'instant » sur une
  base qui en contient trois cents fait conclure à une panne — alors qu'il
  suffisait d'élargir la recherche. Le message le dit, et le bouton le fait.

  Props : total, filtre, reset, nom, icon, vide
--}}
@props([
    'total' => 0,
    'filtre' => false,
    'reset' => null,
    'nom' => 'résultat',
    'icon' => 'document',
    'vide' => null,
])

@if ($total > 0)
    {{-- L'accord se fait sur le nombre, pas sur la présence d'un filtre :
         « 1 client » et « 14 clients » se lisent sans y penser. --}}
    <p class="liste-compteur">
        <strong>{{ number_format($total, 0, ',', ' ') }}</strong>
        {{ $nom }}{{ $total > 1 ? 's' : '' }}

        @if ($filtre)
            <span class="liste-compteur__filtre">{{ __('admin.commun.apres_filtrage') }}</span>

            @if ($reset)
                <a href="{{ $reset }}" class="liste-compteur__reset">{{ __('admin.commun.reinitialiser') }}</a>
            @endif
        @endif
    </p>
@else
    <x-empty-state :icon="$icon"
        :title="$filtre ? 'Aucun résultat pour ces filtres' : 'Rien à afficher pour l\'instant'"
        :message="$filtre
            ? 'Aucun élément ne correspond aux critères choisis. Élargissez la recherche, ou repartez de la liste complète.'
            : $vide">

        @if ($filtre && $reset)
            <x-slot name="action">
                <x-button :href="$reset" size="sm">{{ __('admin.commun.reinitialiser_filtres') }}</x-button>
            </x-slot>
        @endif
    </x-empty-state>
@endif
