{{-- ============================================================================
     LA BARRE PUBLIQUE — direction A (maquette desk-1, mob-01).
     ============================================================================

     Sable, filet bas, collante, opaque (aucun flou au défilement).
     Marque à gauche ; les sections au centre ; à droite « Se connecter » et
     l'action principale.

     LE CONTRAT AVEC L'ACCUEIL (lot 2) — les sections portent ces ancres :
       #fonctionnement   comment ça marche
       #tarifs           l'offre et ses prix
       #carte-pvc        la carte imprimée
     « Exemple » mène à la carte de démonstration, « Aide » à WhatsApp.

     MOBILE FIRST : la barre ne porte que la marque, « Connexion » et le
     menu. Tout le reste — sections, langue, thème, inscription — est dans
     le panneau, où chaque élément a la place d'être touché.

     LE PANNEAU S'OUVRE SANS JAVASCRIPT : le menu est un LIEN vers
     `#menuMobile`. Avec le script, Bootstrap l'ouvre en offcanvas ; sans,
     `:target` l'affiche, et « Fermer » mène à `#contenu`.
============================================================================ --}}
@php
    $cta = auth()->check() ? route('dashboard') : route('register');
    $aide = \App\Support\Whatsapp::assistance();

    $sections = [
        [route('home').'#fonctionnement', __('navigation.public.fonctionnement')],
        [route('home').'#tarifs', __('navigation.public.tarifs')],
        [route('home').'#carte-pvc', __('navigation.public.carte_pvc')],
        [route('profile.demo'), __('navigation.public.exemple')],
    ];
@endphp

<header class="site-nav" data-navbar-scroll>
  <div class="wrap site-nav__in">
    <x-brand class="site-nav__brand" />

    <nav class="site-nav__menu" aria-label="{{ __('navigation.sections_du_site') }}">
      @foreach ($sections as [$url, $libelle])
        <a class="site-nav__link" href="{{ $url }}">{{ $libelle }}</a>
      @endforeach
      @if ($aide)
        <a class="site-nav__link" href="{{ $aide }}" target="_blank" rel="noopener noreferrer">{{ __('navigation.public.aide') }}</a>
      @endif
    </nav>

    <div class="site-nav__right">
      <div class="site-nav__prefs">
        <x-language-toggle />
        <x-theme-toggle />
      </div>

      @auth
        <x-button variant="dark" size="sm" :href="route('dashboard')" class="site-nav__cta">{{ __('navigation.public.mon_espace') }}</x-button>
      @else
        <a class="site-nav__signin" href="{{ route('login') }}">{{ __('navigation.public.connexion') }}</a>
        <x-button variant="dark" size="sm" :href="$cta" class="site-nav__cta">{{ __('navigation.public.creer_compte') }}</x-button>
      @endauth

      <a class="site-nav__burger" href="#menuMobile" role="button"
         data-bs-toggle="offcanvas" data-bs-target="#menuMobile"
         aria-controls="menuMobile" aria-label="{{ __('navigation.ouvrir_menu') }}">
        <x-icone name="menu" />
      </a>
    </div>
  </div>
</header>

<div class="offcanvas offcanvas-end menu-mobile" tabindex="-1" id="menuMobile"
     aria-labelledby="menuMobileTitre">
  <div class="offcanvas-header">
    <x-brand size="sm" :link="false" id="menuMobileTitre" />
    <a href="#contenu" class="btn-close" data-bs-dismiss="offcanvas" role="button"
       aria-label="{{ __('common.actions.fermer') }}"></a>
  </div>

  <div class="offcanvas-body menu-mobile__corps">
    <nav class="menu-mobile__liens" aria-label="{{ __('navigation.sections_du_site') }}">
      @foreach ($sections as [$url, $libelle])
        <a class="menu-mobile__lien" href="{{ $url }}">{{ $libelle }} <x-icone name="chevron-right" /></a>
      @endforeach
      @auth
        <a class="menu-mobile__lien" href="{{ route('dashboard') }}">{{ __('navigation.public.mon_espace') }} <x-icone name="chevron-right" /></a>
      @else
        <a class="menu-mobile__lien" href="{{ route('login') }}">{{ __('navigation.public.se_connecter') }} <x-icone name="chevron-right" /></a>
      @endauth
    </nav>

    <div class="menu-mobile__prefs">
      <span class="menu-mobile__titre">{{ __('navigation.preferences') }}</span>
      <div class="menu-mobile__bascules">
        <x-language-toggle />
        <x-theme-toggle />
      </div>
    </div>

    {{-- L'action principale EN BAS et pleine largeur, sous le pouce. --}}
    <div class="menu-mobile__pied">
      <x-button variant="dark" :href="$cta" :block="true">
        @auth {{ __('navigation.public.mon_espace') }} @else {{ __('navigation.public.creer_compte') }} @endauth
      </x-button>
      <x-whatsapp-fab variante="lien" />
    </div>
  </div>
</div>
