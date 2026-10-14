{{--
  layouts/admin — LA COQUE DE L'ADMINISTRATION, direction A « Teranga ».

      <x-admin-layout title="Clients" subtitle="…">…</x-admin-layout>

  UNE COLONNE, parce qu'il y a onze rubriques — mais CLAIRE : ivoire, filets,
  rubrique active sur fond de grès avec un trait latérite. La colonne vert
  foncé d'avant disait « autre espace » ; la marque ADMIN en tête de colonne
  et le fil d'Ariane le disent désormais sans assombrir la moitié de l'écran.

  LE NOMBRE DE PAIEMENTS À VÉRIFIER est dans la navigation : une seule
  requête COUNT par page, sur l'index (status, created_at) de `payments` —
  son coût ne dépend pas du volume (ChargeDesEcransTest le vérifie).

  SUR TÉLÉPHONE (< 768px) : la même barre d'onglets ANCRÉE que l'espace
  client — quatre rubriques et « Plus », qui ouvre la colonne. « Plus » est
  un lien vers l'ancre de la colonne : Bootstrap l'ouvre en panneau, et sans
  JavaScript `:target` l'affiche quand même.
--}}
@php
    use App\Models\Payment;
    use App\Support\Navigation;

    $admin = Auth::user();

    // UNE requête, constante, indexée : le nombre de paiements en attente.
    $aVerifier = Payment::where('status', Payment::STATUS_PENDING)->count();

    // Le fil d'Ariane : la section de la rubrique active, puis le titre.
    $sectionActive = null;
    foreach (Navigation::rubriquesAdmin() as $section => $rubriques) {
        foreach ($rubriques as [$id, $route, $motif]) {
            if (request()->routeIs($motif)) {
                $sectionActive = $section;
                break 2;
            }
        }
    }
@endphp
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    @include('layouts.partials.head')
</head>
<body class="avec-onglets-bas">
<a href="#contenu" class="skip-link">{{ __('navigation.aller_au_contenu') }}</a>

<div class="adm">

    {{-- ===================== LA COLONNE =====================
         Fixe à partir de 768px ; en dessous, panneau ouvert par « Plus ».
         `offcanvas-md` : la rupture `lg` de $ruptures (768px), pas une borne
         de Bootstrap. --}}
    <aside class="adm-side offcanvas-md offcanvas-start" tabindex="-1"
           id="menuAdmin" aria-labelledby="menuAdminTitre">

        <div class="adm-side__marque" id="menuAdminTitre">
            <x-brand size="sm" :href="route('admin.overview')" />
            <span class="adm-side__tag">{{ __('navigation.admin.tag') }}</span>

            {{-- Fermer, sur téléphone. Un lien vers le contenu : sans
                 JavaScript, il retire la cible `:target` du panneau. --}}
            <a href="#contenu" class="btn-close adm-side__fermer" data-bs-dismiss="offcanvas"
               role="button" aria-label="{{ __('navigation.coque.fermer_menu') }}"></a>
        </div>

        @include('layouts.partials.admin-links')

        <div class="adm-side__moi">
            <span class="adm-avatar" aria-hidden="true">{{ collect(explode(' ', trim($admin->name)))->filter()->take(2)->map(fn ($m) => mb_strtoupper(mb_substr($m, 0, 1)))->implode('') }}</span>
            <span class="adm-side__qui">
                <span class="adm-side__nom">{{ $admin->name }}</span>
                <span class="adm-side__role">{{ __('navigation.coque.role_admin') }}</span>
            </span>
        </div>
    </aside>

    {{-- ===================== LA COLONNE PRINCIPALE ===================== --}}
    <div class="adm-main">

        <header class="adm-top">
            {{-- La marque, sur téléphone seulement : la colonne la porte au-delà. --}}
            <x-brand size="sm" :words="false" :href="route('admin.overview')" class="adm-top__marque" />

            <nav class="adm-top__fil" aria-label="{{ __('navigation.coque.fil_ariane') }}">
                <ol class="fil">
                    <li>{{ __('navigation.coque.section_admin') }}</li>
                    @if ($sectionActive)
                        <li><x-icone name="chevron-right" class="icone--sm" />{{ __('navigation.sections.'.$sectionActive) }}</li>
                    @endif
                    @if ($title)
                        <li aria-current="page"><x-icone name="chevron-right" class="icone--sm" />{{ $title }}</li>
                    @endif
                </ol>
            </nav>

            <div class="adm-top__recherche">
                @include('layouts.partials.recherche', [
                    'action' => route('admin.clients.index'),
                    'libelle' => __('navigation.coque.rechercher_client'),
                    'id' => 'rechercheAdmin',
                    'replie' => false,
                ])
            </div>

            <div class="adm-top__actions">
                <div class="adm-top__loupe">
                    @include('layouts.partials.recherche', [
                        'action' => route('admin.clients.index'),
                        'libelle' => __('navigation.coque.rechercher_client'),
                        'id' => 'rechercheAdminMobile',
                    ])
                </div>

                @include('layouts.partials.notifications-menu', ['espace' => 'admin'])

                @include('layouts.partials.menu-compte', ['espace' => 'admin'])
            </div>
        </header>

        <main id="contenu" class="adm-content">
            <div class="adm-page">

                {{-- EN-TÊTE D'ÉCRAN — titre, sous-titre, actions à droite.
                     Identique sur tous les écrans : la place du bouton
                     d'export ne se cherche pas d'une page à l'autre. --}}
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

{{-- LA BARRE DU BAS — quatre rubriques et « Plus ». --}}
<x-barre-onglets jeu="admin" panneau="menuAdmin" :entrees="[
    ['route' => 'admin.overview',       'actif' => ['admin.overview'],     'icone' => 'vue-ensemble', 'libelle' => __('navigation.admin.court.vue_ensemble')],
    ['route' => 'admin.clients.index',  'actif' => ['admin.clients.*'],    'icone' => 'clients',      'libelle' => __('navigation.admin.court.clients')],
    ['route' => 'admin.payments.index', 'actif' => ['admin.payments.*'],   'icone' => 'paiements',    'libelle' => __('navigation.admin.court.paiements'), 'compte' => $aVerifier],
    ['route' => 'admin.cards.index',    'actif' => ['admin.cards.*'],      'icone' => 'cartes',       'libelle' => __('navigation.admin.court.cartes')],
]" />
</body>
</html>
