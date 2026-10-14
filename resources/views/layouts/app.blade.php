{{--
  layouts/app — LA COQUE DE L'ESPACE CLIENT, direction A « Teranga ».

      <x-app-layout title="Tableau de bord">…</x-app-layout>

  ═══════════════════════════════════════════════════════════════════════════
  SUR ORDINATEUR (≥ 768px) — une barre ivoire, des onglets en haut
  ═══════════════════════════════════════════════════════════════════════════
  La colonne vert foncé a disparu. Six destinations tiennent dans une barre :
  la marque, les onglets (l'onglet actif est signé par la LISIÈRE), puis
  l'aide, la recherche, la cloche et le menu du compte. Le contenu prend
  toute la largeur, jusqu'à 1376px.

  ═══════════════════════════════════════════════════════════════════════════
  SUR TÉLÉPHONE (< 768px) — une barre d'onglets ANCRÉE en bas
  ═══════════════════════════════════════════════════════════════════════════
  Opaque, sans flou, filet supérieur, zone sûre ; le corps de page lui
  réserve sa hauteur (`avec-onglets-bas`). Plus aucun hamburger ni panneau
  latéral pour la navigation : tout ce qui n'est pas dans la barre est dans
  le menu du compte (un <details>, sans JavaScript).

  LES DESTINATIONS NE SONT PAS ÉCRITES ICI : elles viennent de
  App\Support\Navigation, que les deux barres et les tests lisent.

  Le `title` sert la balise <title>. Chaque écran porte son propre <h1>.
--}}
@php
    use App\Support\Navigation;

    $u = Auth::user();
    $aProfil = $u->profile !== null;

    /* « Mon profil » mène à la création tant qu'il n'y a pas de profil :
       c'est la même destination aux yeux de la personne. */
    $urlOnglet = fn (array $o) => $o['route'] === 'profil.index' && ! $aProfil
        ? route('profile.create.step1')
        : route($o['route']);

    $onglets = Navigation::ongletsClient();

    $ongletsDuBas = array_map(fn (array $o) => [
        'url' => $urlOnglet($o),
        'actif' => array_merge($o['actif'], $o['actif_bas'] ?? []),
        'icone' => $o['icone'],
        'libelle' => __($o['court']),
    ], Navigation::ongletsClientDuBas());
@endphp
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    @include('layouts.partials.head')
</head>
<body class="avec-onglets-bas">
<a href="#contenu" class="skip-link">{{ __('navigation.aller_au_contenu') }}</a>

<header class="coque-haut">
    <div class="coque-haut__in">
        <x-brand size="sm" :href="route('dashboard')" class="coque-haut__marque" />

        {{-- LES ONGLETS DU HAUT — à partir de 768px. Libellé court jusqu'à
             1024px, complet au-delà ; le libellé complet reste toujours lu
             par le lecteur d'écran. --}}
        <nav class="coque-onglets" aria-label="{{ __('navigation.principale') }}">
            <ul class="coque-onglets__liste">
                @foreach ($onglets as $onglet)
                    @php $actif = request()->routeIs(...$onglet['actif']); @endphp
                    <li>
                        <a href="{{ $urlOnglet($onglet) }}"
                           @class(['coque-onglet', 'is-actif' => $actif])
                           @if ($actif) aria-current="page" @endif>
                            <x-client-icon :name="$onglet['icone']" />
                            <span class="coque-onglet__long">{{ __($onglet['libelle']) }}</span>
                            <span class="coque-onglet__court" aria-hidden="true">{{ __($onglet['court']) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="coque-haut__outils">
            <x-whatsapp-fab variante="barre" />

            @include('layouts.partials.recherche', [
                'action' => route('recherche'),
                'libelle' => __('navigation.coque.rechercher'),
                'id' => 'rechercheGlobale',
            ])

            @include('layouts.partials.notifications-menu')

            @include('layouts.partials.menu-compte', ['espace' => 'client'])
        </div>
    </div>
</header>

{{-- Bandeau d'essai : une ligne, alarmant seulement sur les trois derniers
     jours. Propre à l'espace client. --}}
@php $sub = $u->activeSubscription(); @endphp
@if ($sub && $sub->isTrial() && ($restants = $sub->daysRemaining()) !== null)
    <div class="app-trial {{ $restants <= 3 ? 'is-urgent' : '' }}">
        <span>{{ trans_choice('navigation.essai.restants', $restants, ['compte' => $restants]) }}</span>
        <a href="{{ route('abonnement.paiement') }}">{{ __('navigation.essai.mettre_a_jour') }}</a>
    </div>
@endif

<main id="contenu" class="coque-contenu">
    <div class="coque-page">
        <x-flash />

        {{ $slot }}
    </div>
</main>

{{-- LA BARRE D'ONGLETS DU BAS — sous 768px. Cinq destinations, libellés
     courts (huit caractères au plus, mesurés à 320px). --}}
<x-barre-onglets jeu="client" :entrees="$ongletsDuBas" />
</body>
</html>
