{{--
  x-auth-tabs — sélecteur Connexion / Créer un compte.

      <x-auth-tabs active="login" />
      <x-auth-tabs active="register" />

  CE SONT DEUX LIENS vers deux routes distinctes, jamais un basculement
  JavaScript : chaque écran garde son URL, son historique et son bouton retour.
  Le sélecteur fonctionne donc sans une ligne de JavaScript.

  aria-current signale l'onglet courant aux lecteurs d'écran ; la classe
  is-active porte l'indication visuelle.
--}}
@props(['active' => 'login'])

<nav class="auth-tabs" aria-label="{{ __('auth.onglets.aria') }}">
    <a href="{{ route('login') }}"
       @class(['auth-tabs__item', 'is-active' => $active === 'login'])
       @if ($active === 'login') aria-current="page" @endif>
        {{ __('auth.onglets.connexion') }}
    </a>

    <a href="{{ route('register') }}"
       @class(['auth-tabs__item', 'is-active' => $active === 'register'])
       @if ($active === 'register') aria-current="page" @endif>
        {{ __('auth.onglets.inscription') }}
    </a>
</nav>
