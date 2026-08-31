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
<a href="#contenu" class="skip-link">{{ __('navigation.aller_au_contenu') }}</a>

<div class="adm">

    {{-- ===================== COLONNE LATÉRALE ===================== --}}
    {{-- `offcanvas-md` et non `offcanvas-lg` : la colonne redevient fixe à
     768px, la rupture `lg` de `$ruptures`, et non à 992px — une borne de
     Bootstrap qui n'appartient pas à notre échelle.

     LE DÉCALAGE LAISSAIT UN TROU. Le dock disparaît à 768px ; la colonne
     ne réapparaissait qu'à 992. Entre les deux, aucune navigation visible
     — seulement un bouton hamburger. Le relevé l'a mesuré. --}}
    <aside class="adm-side offcanvas-md offcanvas-start" tabindex="-1"
           id="menuLateral" aria-labelledby="menuLateralTitre">

        <div class="adm-side__marque" id="menuLateralTitre">
            <x-brand size="sm" tone="light" :href="route('dashboard')" />
            <span class="adm-side__portail">{{ __('navigation.coque.espace_client') }}</span>
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

        {{-- PLUS DE HAMBURGER.
             Il vivait ici, en haut à gauche — le coin le plus éloigné du
             pouce sur un téléphone — et faisait double emploi avec le dock.
             Son rôle est repris par l'entrée « Plus » du dock, en bas. --}}
        <header class="adm-top">
            <span class="adm-top__section">{{ __('navigation.coque.section_client') }}</span>

            {{-- Recherche : formulaire GET, terme en query string.
                 Partageable, rechargeable, sans JavaScript. --}}
            <form method="GET" action="{{ route('recherche') }}" class="adm-search" role="search">
                <label for="rechercheGlobale" class="visually-hidden">{{ __('navigation.coque.rechercher') }}</label>
                <svg class="adm-search__icone" width="15" height="15" viewBox="0 0 16 16"
                     fill="currentColor" aria-hidden="true">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
                <input type="search" id="rechercheGlobale" name="q" class="adm-search__champ"
                       value="{{ request('q') }}" placeholder="{{ __('navigation.coque.rechercher') }}" autocomplete="off">
            </form>

            <div class="adm-top__actions">
                @include('layouts.partials.notifications-menu')

                <x-language-toggle />
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
                        <li><a class="dropdown-item" href="{{ route('compte.edit') }}">{{ __('navigation.coque.mon_compte') }}</a></li>

                        {{-- Passerelle vers l'administration, pour les seuls
                             comptes qui en ont une. Elle n'apparaît pas
                             ailleurs : un client ne doit pas deviner qu'un
                             back-office existe. --}}
                        @if ($u->isAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.home') }}">{{ __('navigation.coque.administration') }}</a></li>
                        @endif

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

        {{-- Bandeau d'essai : une ligne, alarmant seulement sur les trois
             derniers jours. Propre à l'espace client. --}}
        @php $sub = $u->activeSubscription(); @endphp
        @if ($sub && $sub->isTrial() && ($restants = $sub->daysRemaining()) !== null)
            <div class="app-trial {{ $restants <= 3 ? 'is-urgent' : '' }}">
                {{-- trans_choice et non une concaténation : l'anglais ne place
                     ni le pluriel ni les mots dans le même ordre. --}}
                <span>{{ trans_choice('navigation.essai.restants', $restants, ['compte' => $restants]) }}</span>
                <a href="{{ route('abonnement.paiement') }}">{{ __('navigation.essai.mettre_a_jour') }}</a>
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

{{-- ESPACE CLIENT — le message est déduit de l'écran exact : étape 2 de la
     création, paiement, QR Code, statistiques. C'est ce qui permet à l'équipe
     de savoir où la personne était sans avoir à le lui demander. --}}
{{-- LE DOCK — navigation permanente sous 768px.
     Cinq destinations : c'est exactement ce que compte l'espace client,
     donc aucune n'est reléguée. Le panneau latéral reste accessible par
     le bouton de la barre supérieure pour l'aide et la déconnexion. --}}
{{-- LES LIBELLÉS VIENNENT DE `navigation.client.court.*`, PAS DE LA COLONNE.
     Le dock reprenait les libellés longs : à 320px, « Tableau de bord »,
     « Mon QR Code » et « Mon abonnement » s'affichaient tronqués — trois
     entrées sur cinq. Voir le commentaire dans lang/fr/navigation.php. --}}
<x-dock jeu="client" panneau="menuLateral" :entrees="[
    ['route' => 'dashboard',    'icone' => 'grille',   'libelle' => __('navigation.client.court.tableau_de_bord')],
    ['route' => 'profil.index', 'icone' => 'personne', 'libelle' => __('navigation.client.court.mon_profil')],
    ['route' => 'carte.qr',     'icone' => 'qr',       'libelle' => __('navigation.client.court.mon_qr')],
    ['route' => 'statistiques', 'icone' => 'courbe',   'libelle' => __('navigation.client.court.statistiques')],
]" />

<x-whatsapp-fab />
</body>
</html>
