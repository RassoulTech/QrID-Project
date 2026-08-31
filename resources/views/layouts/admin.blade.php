{{--
  layouts/admin — espace administrateur.

  COLONNE VERT TRÈS FONCÉ à gauche, contenu clair à droite. Le contraste avec
  l'espace client, dont le menu est blanc, est volontaire : on doit savoir en
  un coup d'œil si l'on gère SON compte ou LA plateforme.

  La colonne garde son fond sombre dans les deux thèmes. Elle n'est pas une
  surface parmi d'autres, c'est un repère — la voir changer de couleur avec le
  thème lui ferait perdre exactement ce qu'elle apporte.

  <x-admin-layout title="Clients" subtitle="…">…</x-admin-layout>
--}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    @include('layouts.partials.head')
</head>
<body>
<a href="#contenu" class="skip-link">{{ __('navigation.aller_au_contenu') }}</a>

<div class="adm">

    {{-- ===================== COLONNE LATÉRALE =====================
         Hors mobile : colonne fixe. Sous 992px : offcanvas Bootstrap natif,
         aucune navigation ne dépend d'un script maison. --}}
    {{-- `offcanvas-md` et non `offcanvas-lg` : la colonne redevient fixe à
     768px, la rupture `lg` de `$ruptures`, et non à 992px — une borne de
     Bootstrap qui n'appartient pas à notre échelle.

     LE DÉCALAGE LAISSAIT UN TROU. Le dock disparaît à 768px ; la colonne
     ne réapparaissait qu'à 992. Entre les deux, aucune navigation visible
     — seulement un bouton hamburger. Le relevé l'a mesuré. --}}
    <aside class="adm-side offcanvas-md offcanvas-start" tabindex="-1"
           id="menuAdmin" aria-labelledby="menuAdminTitre">

        <div class="adm-side__marque" id="menuAdminTitre">
            <x-brand size="sm" tone="light" :href="route('admin.overview')" />
            <span class="adm-side__portail">{{ __('navigation.coque.portail_admin') }}</span>
        </div>

        @include('layouts.partials.admin-links')
    </aside>

    {{-- ===================== COLONNE PRINCIPALE ===================== --}}
    <div class="adm-main">

        {{-- PLUS DE HAMBURGER — voir le commentaire du gabarit client.
             L'administration a douze destinations : « Plus » y est d'autant
             plus utile, puisque huit d'entre elles vivent dans le panneau. --}}
        <header class="adm-top">
            <span class="adm-top__section">{{ __('navigation.coque.section_admin') }}</span>

            {{-- Recherche globale : GET, terme en query string. Partageable,
                 rechargeable, sans JavaScript. Elle mène à la liste des
                 clients, seule liste où une recherche libre a un sens. --}}
            <form method="GET" action="{{ route('admin.clients.index') }}"
                  class="adm-search" role="search">
                <label for="rechercheAdmin" class="visually-hidden">{{ __('navigation.coque.rechercher_client') }}</label>
                <svg class="adm-search__icone" width="15" height="15" viewBox="0 0 16 16"
                     fill="currentColor" aria-hidden="true">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
                <input type="search" id="rechercheAdmin" name="q" class="adm-search__champ"
                       value="{{ request('q') }}" placeholder="{{ __('navigation.coque.rechercher_client') }}" autocomplete="off">
            </form>

            <div class="adm-top__actions">
                @include('layouts.partials.notifications-menu', ['espace' => 'admin'])

                <x-language-toggle />
                <x-theme-toggle />

                @php
                    $admin = Auth::user();
                    $initiales = collect(explode(' ', $admin->name))
                        ->take(2)
                        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
                        ->implode('');
                @endphp

                <div class="dropdown">
                    <button class="adm-top__user" type="button" id="menuAdminCompte"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-sm-inline">{{ $admin->name }}</span>
                        <span class="adm-avatar" aria-hidden="true">{{ $initiales }}</span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuAdminCompte">
                        <li><span class="dropdown-item-text small text-secondary">{{ $admin->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('dashboard') }}">{{ __('navigation.coque.retour_espace_client') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('compte.edit') }}">{{ __('navigation.coque.mon_compte') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">{{ __('navigation.coque.se_deconnecter') }}</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main id="contenu" class="adm-content">
            <div class="adm-page">

                {{-- EN-TÊTE D'ÉCRAN — titre, sous-titre, actions à droite.
                     Identique sur les huit écrans : la place du bouton
                     d'export ne doit pas se chercher d'une page à l'autre. --}}
                <div class="adm-head">
                    <div class="adm-head__texte">
                        <h1 class="adm-head__titre">{{ $title }}</h1>
                        @isset($subtitle)
                            <p class="adm-head__sous">{{ $subtitle }}</p>
                        @endisset
                    </div>

                    @isset($actions)
                        <div class="adm-head__actions">{{ $actions }}</div>
                    @endisset
                </div>

                <x-flash />

                {{ $slot }}
            </div>
        </main>
    </div>
</div>

{{-- LE DOCK DE L'ADMINISTRATION.
     Douze destinations existent ; cinq entrent ici. Un dock qui déborde
     n'est plus un dock, c'est une barre de défilement — le reste demeure
     dans le panneau latéral, à un geste du bouton de la barre. --}}
<x-dock jeu="admin" panneau="menuAdmin" :entrees="[
    ['route' => 'admin.overview',        'icone' => 'vue-ensemble', 'libelle' => __('navigation.admin.court.vue_ensemble')],
    ['route' => 'admin.clients.index',   'icone' => 'clients',      'libelle' => __('navigation.admin.court.clients')],
    ['route' => 'admin.payments.index',  'icone' => 'paiements',    'libelle' => __('navigation.admin.court.paiements')],
    ['route' => 'admin.cards.index',     'icone' => 'modeles',      'libelle' => __('navigation.admin.court.cartes')],
]" />
</body>
</html>
