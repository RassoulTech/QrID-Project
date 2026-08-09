{{-- NAVBAR — logo à gauche, menu central, Connexion + bouton à droite.
     Menu mobile : offcanvas Bootstrap natif (data-bs-*), aucun JS écrit. --}}
@php $cta = auth()->check() ? route('dashboard') : route('register'); @endphp

<nav class="site-nav" data-navbar-scroll>
  <div class="wrap site-nav__in">
    <x-brand class="site-nav__brand" />

    <div class="site-nav__menu">
      <a class="site-nav__link" href="{{ route('home') }}#produits">Produits</a>
      <a class="site-nav__link" href="{{ route('home') }}#ressources">Ressources</a>
      <a class="site-nav__link" href="{{ route('home') }}#tarifs">Tarifs</a>
    </div>

    <div class="site-nav__right">
      <x-theme-toggle />

      @auth
        <x-button variant="dark" size="sm" :href="route('dashboard')">Mon espace</x-button>
      @else
        <a class="site-nav__signin" href="{{ route('login') }}">Connexion</a>
        <x-button variant="dark" size="sm" :href="$cta">Créer un compte</x-button>
      @endauth

      <button class="site-nav__burger" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#menuMobile"
              aria-controls="menuMobile" aria-label="Ouvrir le menu">
        <svg width="22" height="22" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
        </svg>
      </button>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-end" tabindex="-1" id="menuMobile" aria-labelledby="menuMobileTitre">
  <div class="offcanvas-header">
    <h2 class="offcanvas-title site-nav__brand" id="menuMobileTitre">{{ config('app.name') }}</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column gap-3">
    <a class="site-nav__link" href="{{ route('home') }}#produits">Produits</a>
    <a class="site-nav__link" href="{{ route('home') }}#ressources">Ressources</a>
    <a class="site-nav__link" href="{{ route('home') }}#tarifs">Tarifs</a>
    @auth
      <x-button variant="dark" :href="route('dashboard')" :block="true">Mon espace</x-button>
    @else
      <a class="site-nav__link" href="{{ route('login') }}">Connexion</a>
      <x-button variant="dark" :href="$cta" :block="true">Créer un compte</x-button>
    @endauth
  </div>
</div>
