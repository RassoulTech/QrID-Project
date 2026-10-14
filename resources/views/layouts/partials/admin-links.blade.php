{{--
  Menu de l'administration — onze rubriques en quatre sections, puis le retour.

  LES RUBRIQUES VIENNENT DE App\Support\Navigation::rubriquesAdmin() : la
  colonne, le fil d'Ariane et la barre du bas lisent la même liste.

  L'ENTRÉE ACTIVE EST DÉCIDÉE PAR LE SERVEUR (routeIs) : fond de grès, trait
  latérite à gauche, graisse — et aria-current. Jamais la couleur seule.

  « Paiements » porte le NOMBRE de paiements à vérifier, calculé une fois par
  le gabarit (`$aVerifier`) : un chiffre dans un cachet « en attente », dit
  en toutes lettres au lecteur d'écran.

  ÉCRANS EN CONSTRUCTION : une rubrique dont la vue n'existe pas encore
  s'affiche non cliquable plutôt que de mener à une erreur 500.

  SUR TÉLÉPHONE, la colonne devient le panneau « Plus » : ce que la barre du
  bas porte déjà en est retiré (`adm-nav__item--dans-le-dock`).
--}}
@php
    use App\Support\Navigation;

    $routesDuDock = Navigation::routesDuDockAdmin();
    $aVerifier = $aVerifier ?? 0;

    $cle = fn (string $id) => 'navigation.admin.'.str_replace('-', '_', $id);

    /* Un titre posé au-dessus du vide se lit comme une page cassée. */
    $sectionEntiereDansLeDock = [];
    foreach (Navigation::rubriquesAdmin() as $nom => $entrees) {
        $hors = array_filter($entrees, fn ($e) => ! Navigation::estDansLeDock($e[1], $routesDuDock));
        $sectionEntiereDansLeDock[$nom] = $hors === [];
    }
@endphp

<nav class="adm-nav" aria-label="{{ __('navigation.administration') }}">
    @foreach (Navigation::rubriquesAdmin() as $section => $entrees)
        <p @class([
            'adm-nav__titre',
            'adm-nav__titre--dans-le-dock' => $sectionEntiereDansLeDock[$section],
        ])>{{ __('navigation.sections.'.$section) }}</p>

        <ul class="adm-nav__liste">
            @foreach ($entrees as [$id, $route, $motif, $vue])
                @php
                    $libelle = __($cle($id));
                    $existe = Route::has($route) && View::exists($vue);
                    $actif = $existe && request()->routeIs($motif);
                @endphp

                <li @class(['adm-nav__item--dans-le-dock' => Navigation::estDansLeDock($route, $routesDuDock)])>
                    @if ($existe)
                        <a href="{{ route($route) }}"
                           @class(['adm-nav__lien', 'is-active' => $actif])
                           @if ($actif) aria-current="page" @endif>
                            <x-admin-icon :name="$id" />
                            <span>{{ $libelle }}</span>

                            @if ($id === 'paiements' && $aVerifier > 0)
                                <span class="adm-nav__compte" aria-hidden="true">{{ $aVerifier > 99 ? '99+' : $aVerifier }}</span>
                                <span class="visually-hidden">{{ trans_choice('navigation.admin.a_verifier', $aVerifier, ['compte' => $aVerifier]) }}</span>
                            @endif
                        </a>
                    @else
                        <span class="adm-nav__lien is-muted" aria-disabled="true"
                              title="{{ __('navigation.admin.en_construction') }}">
                            <x-admin-icon :name="$id" />
                            <span>{{ $libelle }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endforeach

    {{-- BAS DE COLONNE — le retour à l'espace client et la déconnexion.
         La déconnexion est un formulaire POST. --}}
    <div class="adm-nav__bas">
        <a class="adm-nav__lien" href="{{ route('dashboard') }}">
            <x-admin-icon name="retour" />
            <span>{{ __('navigation.coque.retour_espace_client') }}</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="adm-nav__lien adm-nav__lien--action">
                <x-admin-icon name="deconnexion" />
                <span>{{ __('navigation.coque.deconnexion') }}</span>
            </button>
        </form>
    </div>
</nav>
