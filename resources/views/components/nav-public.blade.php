{{-- ============================================================================
     NAVBAR PUBLIQUE — logo à gauche, menu central, actions à droite.
     ============================================================================

     MOBILE FIRST : LA BARRE NE PORTE QUE DEUX CHOSES.

     Elle en portait cinq — logo, langue, thème, « Créer un compte », menu —
     et à 375px les deux bascules se chevauchaient avec le bouton, qui
     débordait lui-même sous le menu. Une barre de navigation qui déborde est
     pire qu'une barre pauvre : elle donne l'impression d'un site cassé avant
     même qu'on ait lu une ligne.

     En dessous de 992px : le logo et le menu, rien d'autre. TOUT le reste —
     liens, langue, thème, connexion, inscription — vit dans le panneau
     latéral, où chaque élément a la place d'être touché.

     Le panneau est l'offcanvas natif de Bootstrap, piloté par les attributs
     data-bs-*. Aucune ligne de JavaScript à écrire ni à maintenir.
============================================================================ --}}
@php $cta = auth()->check() ? route('dashboard') : route('register'); @endphp

<nav class="site-nav" data-navbar-scroll>
  <div class="wrap site-nav__in">
    <x-brand class="site-nav__brand" />

    <div class="site-nav__menu">
      <a class="site-nav__link" href="{{ route('home') }}#produits">{{ __('navigation.public.produits') }}</a>
      <a class="site-nav__link" href="{{ route('home') }}#ressources">{{ __('navigation.public.ressources') }}</a>
      <a class="site-nav__link" href="{{ route('home') }}#tarifs">{{ __('navigation.public.tarifs') }}</a>
    </div>

    <div class="site-nav__right">
      {{-- Masqué en dessous de 992px : ces quatre éléments sont repris à
           l'identique dans le panneau. --}}
      <div class="site-nav__actions">
        <x-language-toggle />
        <x-theme-toggle />

        @auth
          <x-button variant="dark" size="sm" :href="route('dashboard')">{{ __('navigation.public.mon_espace') }}</x-button>
        @else
          <a class="site-nav__signin" href="{{ route('login') }}">{{ __('navigation.public.connexion') }}</a>
          <x-button variant="dark" size="sm" :href="$cta">{{ __('navigation.public.creer_compte') }}</x-button>
        @endauth
      </div>

      {{-- 44×44 : la cible tactile minimale. Le bouton n'en faisait que 34,
           et un menu qu'on rate une fois sur trois n'est pas un menu. --}}
      <button class="site-nav__burger" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#menuMobile"
              aria-controls="menuMobile" aria-label="{{ __('navigation.ouvrir_menu') }}">
        <svg width="22" height="22" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
        </svg>
      </button>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-end menu-mobile" tabindex="-1" id="menuMobile"
     aria-labelledby="menuMobileTitre">
  <div class="offcanvas-header">
    <h2 class="offcanvas-title site-nav__brand" id="menuMobileTitre">{{ config('app.name') }}</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('common.actions.fermer') }}"></button>
  </div>

  <div class="offcanvas-body menu-mobile__corps">
    <nav class="menu-mobile__liens" aria-label="{{ __('navigation.sections_du_site') }}">
      <a class="menu-mobile__lien" href="{{ route('home') }}#produits">{{ __('navigation.public.produits') }}</a>
      <a class="menu-mobile__lien" href="{{ route('home') }}#ressources">{{ __('navigation.public.ressources') }}</a>
      <a class="menu-mobile__lien" href="{{ route('home') }}#tarifs">{{ __('navigation.public.tarifs') }}</a>
      @auth
        <a class="menu-mobile__lien" href="{{ route('dashboard') }}">{{ __('navigation.public.mon_espace') }}</a>
      @else
        <a class="menu-mobile__lien" href="{{ route('login') }}">{{ __('navigation.public.connexion') }}</a>
      @endauth
    </nav>

    {{-- LES PRÉFÉRENCES SONT DANS LE PANNEAU, PAS DANS LA BARRE.
         Un client qui cherche à changer de langue ouvre le menu : c'est le
         seul endroit où il pense à chercher sur téléphone. --}}
    <div class="menu-mobile__prefs">
      <span class="menu-mobile__titre">{{ __('navigation.preferences') }}</span>
      <div class="menu-mobile__bascules">
        <x-language-toggle />
        <x-theme-toggle />
      </div>
    </div>

    {{-- L'action principale est EN BAS et pleine largeur : c'est là que le
         pouce se pose naturellement sur un téléphone tenu d'une main. --}}
    <div class="menu-mobile__pied">
      <x-button variant="dark" :href="$cta" :block="true">
        @auth {{ __('navigation.public.mon_espace') }} @else {{ __('navigation.public.creer_compte') }} @endauth
      </x-button>
    </div>
  </div>
</div>
