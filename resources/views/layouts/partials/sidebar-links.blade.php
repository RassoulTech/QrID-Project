{{--
  MENU DE L'ESPACE CLIENT.

  Il partage la coque et les classes de l'administration (`adm-`). Ce n'est
  pas une coquetterie d'économie : c'étaient DEUX jeux de composants
  identiques — colonne, barre, recherche, avatar, contenu — et le moindre
  défaut devait être corrigé deux fois. Un fond de colonne effacé par
  Bootstrap a dû l'être exactement deux fois avant qu'on ne s'en aperçoive.

  LES ENTRÉES N'ONT PAS CHANGÉ : les mêmes sept que précédemment, seulement
  regroupées en sections comme dans l'administration. Aucune ajoutée, aucune
  retirée.

  « Mon profil » reste active pendant TOUT le parcours de création : sans
  cela, le menu perdrait son repère au milieu des trois étapes.
--}}
@php
    $aProfil = Auth::user()?->profile !== null;

    $profilActif = request()->routeIs('profil.index')
        || request()->routeIs('profile.create.*')
        || request()->routeIs('profile.edit')
        || request()->routeIs('profile.preview');

    $sections = [
        'pilotage' => [
            [__('navigation.client.tableau_de_bord'), route('dashboard'), request()->routeIs('dashboard'), 'grille'],
            [__('navigation.client.statistiques'), route('statistiques'), request()->routeIs('statistiques'), 'courbe'],
        ],
        'ma_carte' => [
            // Sans profil, l'entrée mène à la première étape de création :
            // consulter un profil inexistant n'aurait rien à montrer.
            [__('navigation.client.mon_profil'), $aProfil ? route('profil.index') : route('profile.create.step1'), $profilActif, 'personne'],
            [__('navigation.client.mon_qr'), route('carte.qr'), request()->routeIs('carte.qr'), 'qr'],
        ],
        'compte' => [
            // Mène à l'écran des formules, qui sert aussi bien la première
            // souscription que le renouvellement, selon l'état du compte.
            [__('navigation.client.mon_abonnement'), route('abonnement.paiement'), request()->routeIs('abonnement.*'), 'carte-bancaire'],
        ],
    ];
@endphp

{{-- x-client-icon reçoit un identifiant propre (« grille », « qr »), pas le
     libellé : le menu peut être traduit sans que les icônes ne bougent. --}}
<nav class="adm-nav" aria-label="{{ __('navigation.principale') }}">
    @foreach ($sections as $section => $entrees)
        <p class="adm-nav__titre">{{ __('navigation.sections.'.$section) }}</p>

        <ul class="adm-nav__liste">
            @foreach ($entrees as [$libelle, $url, $actif, $icone])
                <li>
                    <a href="{{ $url }}"
                       @class(['adm-nav__lien', 'is-active' => $actif])
                       @if ($actif) aria-current="page" @endif>
                        <x-client-icon :name="$icone" />
                        <span>{{ $libelle }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach

    {{-- BAS DE MENU — détaché du reste par un trait.
         L'aide ouvre WhatsApp : lien externe, donc rel="noopener". --}}
    <div class="adm-nav__bas">
        <a class="adm-nav__lien" href="{{ config('registration.support_whatsapp') }}"
           target="_blank" rel="noopener">
            <x-client-icon name="aide" />
            <span>{{ __('navigation.coque.aide') }}</span>
        </a>

        {{-- Formulaire POST : en GET, une simple image distante suffirait à
             déconnecter le client. --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="adm-nav__lien adm-nav__lien--action">
                <x-client-icon name="sortie" />
                <span>{{ __('navigation.coque.deconnexion') }}</span>
            </button>
        </form>
    </div>
</nav>
