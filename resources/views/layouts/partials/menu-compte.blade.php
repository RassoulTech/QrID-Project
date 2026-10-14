{{--
  LE MENU DU COMPTE — l'avatar en haut à droite, un <details> sans JavaScript.

      @include('layouts.partials.menu-compte', ['espace' => 'client'])

  Il porte ce qui n'est pas une destination de premier rang : le compte,
  l'abonnement (côté client), la passerelle vers l'autre espace, les deux
  préférences d'affichage (langue, thème) et la déconnexion.

  SANS JAVASCRIPT, il s'ouvre et se ferme nativement. C'est ce qui permet
  d'avoir retiré l'offcanvas du client : tout ce qui n'est pas dans la
  barre d'onglets est ici, à un geste, script ou pas.

  La déconnexion est un formulaire POST : en GET, une simple image distante
  suffirait à déconnecter quelqu'un.
--}}
@php
    $u = Auth::user();
    $espace = $espace ?? 'client';
    $initiales = collect(explode(' ', trim($u->name)))
        ->filter()
        ->take(2)
        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
        ->implode('');
    $role = $espace === 'admin' ? __('navigation.coque.role_admin') : __('navigation.coque.role_client');
@endphp

<details class="menu-coque menu-compte" data-menu>
    <summary class="menu-compte__declencheur" aria-label="{{ __('navigation.coque.menu_compte') }}">
        {{-- LA PHOTO DU COMPTE, ou les initiales — le cas normal. --}}
        @if ($espace === 'client' && ($avatarUrl = $u->avatarUrl()))
            <img src="{{ $avatarUrl }}" alt="" class="adm-avatar adm-avatar--photo" width="40" height="40" loading="lazy">
        @else
            <span class="adm-avatar" aria-hidden="true">{{ $initiales }}</span>
        @endif

        <span class="menu-compte__qui">
            <span class="menu-compte__nom">{{ $u->name }}</span>
            <span class="menu-compte__role">{{ $role }}</span>
        </span>

        <x-icone name="chevron-down" class="icone--sm menu-compte__chevron" />
    </summary>

    <div class="menu-coque__panneau">
        <div class="menu-coque__tete">
            <span class="adm-avatar" aria-hidden="true">{{ $initiales }}</span>
            <span class="menu-coque__qui">
                <span class="menu-coque__nom">{{ $u->name }}</span>
                <span class="menu-coque__courriel">{{ $u->email }}</span>
            </span>
        </div>

        @if ($espace === 'admin')
            <a class="menu-coque__lien" href="{{ route('dashboard') }}">
                <x-icone name="arrow-left" /> {{ __('navigation.coque.retour_espace_client') }}
            </a>
        @endif

        <a class="menu-coque__lien" href="{{ route('compte.edit') }}">
            <x-icone name="user" /> {{ __('navigation.coque.mon_compte') }}
        </a>

        @if ($espace === 'client')
            <a class="menu-coque__lien" href="{{ route('abonnement.paiement') }}">
                <x-icone name="credit-card" /> {{ __('navigation.client.mon_abonnement') }}
            </a>

            {{-- La passerelle vers l'administration, pour les seuls comptes
                 qui en ont une : un client ne doit pas deviner qu'un
                 back-office existe. --}}
            @if ($u->isAdmin())
                <a class="menu-coque__lien" href="{{ route('admin.home') }}">
                    <x-icone name="shield-check" /> {{ __('navigation.coque.administration') }}
                </a>
            @endif
        @endif

        <div class="menu-coque__separateur" role="presentation"></div>

        <div class="menu-coque__prefs">
            <span>{{ __('navigation.coque.preferences') }}</span>
            <div>
                <x-language-toggle />
                <x-theme-toggle />
            </div>
        </div>

        <div class="menu-coque__separateur" role="presentation"></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="menu-coque__lien menu-coque__lien--danger">
                <x-icone name="log-out" /> {{ __('navigation.coque.se_deconnecter') }}
            </button>
        </form>
    </div>
</details>
