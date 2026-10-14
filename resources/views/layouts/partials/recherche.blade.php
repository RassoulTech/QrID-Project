{{--
  LA RECHERCHE DE LA COQUE — un formulaire GET, sans JavaScript.

      @include('layouts.partials.recherche', [
          'action' => route('recherche'),
          'libelle' => __('navigation.coque.rechercher'),
          'id' => 'rechercheGlobale',
          'replie' => true,          // dans un menu <details> (icône loupe)
      ])

  GET et terme en query string : partageable, rechargeable, sans script.

  `replie` : la loupe ouvre un petit panneau. C'est la forme du téléphone,
  et celle de l'espace client sur ordinateur (la maquette ne lui donne pas
  de champ permanent). L'administration, qui cherche un client à chaque
  écran, garde un champ visible sur ordinateur.
--}}
@php
    $replie = $replie ?? true;
@endphp

@if ($replie)
    <details class="menu-coque recherche-coque" data-menu>
        <summary class="btn-icone" aria-label="{{ $libelle }}">
            <x-icone name="search" />
        </summary>

        <div class="menu-coque__panneau">
            <form method="GET" action="{{ $action }}" class="menu-coque__recherche" role="search">
                <label for="{{ $id }}" class="visually-hidden">{{ $libelle }}</label>
                <input type="search" id="{{ $id }}" name="q" value="{{ request('q') }}"
                       placeholder="{{ $libelle }}" autocomplete="off">
                <button type="submit" class="btn-icone" aria-label="{{ __('navigation.coque.lancer_recherche') }}">
                    <x-icone name="arrow-right" />
                </button>
            </form>
        </div>
    </details>
@else
    <form method="GET" action="{{ $action }}" class="adm-search" role="search">
        <label for="{{ $id }}" class="visually-hidden">{{ $libelle }}</label>
        <x-icone name="search" class="adm-search__icone" />
        <input type="search" id="{{ $id }}" name="q" class="adm-search__champ"
               value="{{ request('q') }}" placeholder="{{ $libelle }}" autocomplete="off">
    </form>
@endif
