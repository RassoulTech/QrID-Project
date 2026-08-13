{{--
  layouts/app — coque de l'espace client.

  ELLE PARTAGE LA COQUE DE L'ADMINISTRATION. Les classes `adm-` ne sont plus
  celles « de l'admin » : ce sont celles de l'application connectée, des deux
  côtés. Auparavant, colonne, barre supérieure, recherche, avatar et zone de
  contenu existaient en double sous les préfixes `app-` et `adm-` — huit
  familles de composants écrites deux fois, pour deux résultats identiques.

  Le prix de ce doublon n'était pas théorique : Bootstrap efface le fond des
  éléments `offcanvas-lg` au-dessus de 992px, et le défaut a dû être corrigé
  dans les deux feuilles, à deux moments différents.

  CE QUI RESTE PROPRE À CET ESPACE : le bandeau d'essai gratuit, la recherche
  qui vise les contenus du client, et les icônes en trait plutôt qu'en aplat.

  Sous 992px, la colonne se replie dans l'offcanvas Bootstrap natif : aucune
  navigation ne dépend d'un script maison.

  <x-app-layout title="Tableau de bord">…</x-app-layout>
--}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    @include('layouts.partials.head')
</head>
<body>
<a href="#contenu" class="skip-link">Aller au contenu</a>

<div class="adm">

    {{-- ===================== COLONNE LATÉRALE ===================== --}}
    <aside class="adm-side offcanvas-lg offcanvas-start" tabindex="-1"
           id="menuLateral" aria-labelledby="menuLateralTitre">

        <div class="adm-side__marque" id="menuLateralTitre">
            <x-brand size="sm" tone="light" :href="route('dashboard')" />
            <span class="adm-side__portail">Espace client</span>
        </div>

        @include('layouts.partials.sidebar-links')
    </aside>

    {{-- ===================== COLONNE PRINCIPALE ===================== --}}
    <div class="adm-main">

        @php
            $u = Auth::user();
            $initiales = collect(explode(' ', $u->name))
                ->take(2)
                ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
                ->implode('');
        @endphp

        <header class="adm-top">
            <button type="button" class="adm-top__burger"
                    data-bs-toggle="offcanvas" data-bs-target="#menuLateral"
                    aria-controls="menuLateral" aria-label="Ouvrir le menu">
                <svg width="22" height="22" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
                </svg>
            </button>

            <span class="adm-top__section">Mon espace</span>

            {{-- Recherche : formulaire GET, terme en query string.
                 Partageable, rechargeable, sans JavaScript. --}}
            <form method="GET" action="{{ route('recherche') }}" class="adm-search" role="search">
                <label for="rechercheGlobale" class="visually-hidden">Rechercher</label>
                <svg class="adm-search__icone" width="15" height="15" viewBox="0 0 16 16"
                     fill="currentColor" aria-hidden="true">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
                <input type="search" id="rechercheGlobale" name="q" class="adm-search__champ"
                       value="{{ request('q') }}" placeholder="Rechercher" autocomplete="off">
            </form>

            <div class="adm-top__actions">
                @include('layouts.partials.notifications-menu')

                <x-theme-toggle />

                <div class="dropdown">
                    <button class="adm-top__user" type="button" id="menuCompte"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-sm-inline">{{ $u->name }}</span>
                        <span class="adm-avatar" aria-hidden="true">{{ $initiales }}</span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuCompte">
                        <li><span class="dropdown-item-text small text-secondary">{{ $u->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('compte.edit') }}">Mon compte</a></li>

                        {{-- Passerelle vers l'administration, pour les seuls
                             comptes qui en ont une. Elle n'apparaît pas
                             ailleurs : un client ne doit pas deviner qu'un
                             back-office existe. --}}
                        @if ($u->isAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.home') }}">Administration</a></li>
                        @endif

                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">Se déconnecter</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        {{-- Bandeau d'essai : une ligne, alarmant seulement sur les trois
             derniers jours. Propre à l'espace client. --}}
        @php $sub = $u->activeSubscription(); @endphp
        @if ($sub && $sub->isTrial() && ($restants = $sub->daysRemaining()) !== null)
            <div class="app-trial {{ $restants <= 3 ? 'is-urgent' : '' }}">
                <span>Il vous reste {{ $restants }} jour{{ $restants > 1 ? 's' : '' }} d'essai gratuit</span>
                <a href="{{ route('abonnement.paiement') }}">Mettre à jour mon plan</a>
            </div>
        @endif

        <main id="contenu" class="adm-content">
            <div class="adm-page">
                {{-- AUCUN EN-TÊTE POSÉ ICI, contrairement à la coque
                     d'administration. Les dix écrans clients portent déjà
                     leur propre <h1> — « Mon profil », « Mon QR Code »,
                     « Votre carte est prête ». En ajouter un second donnerait
                     deux titres sur chaque page, et deux titres de niveau 1
                     désorientent un lecteur d'écran autant qu'un œil.

                     Le `title` passé au composant sert la balise <title> du
                     navigateur, pas l'affichage. --}}
                <x-flash />

                {{ $slot }}
            </div>
        </main>
    </div>
</div>

{{-- ESPACE CLIENT — le message pré-rempli est différent de celui de la page
     d'accueil : ici on s'adresse à quelqu'un qui a déjà un compte, et dont la
     question porte le plus souvent sur sa carte ou son abonnement. Un message
     générique l'obligerait à tout réécrire. --}}
<x-whatsapp-fab message="Bonjour, j'ai besoin d'aide sur mon espace {{ config('app.name') }}." />
</body>
</html>
