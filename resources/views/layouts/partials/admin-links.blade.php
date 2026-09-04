{{--
  Menu de l'espace administrateur — onze entrées, trois sections plus le bas.

  L'ENTRÉE ACTIVE EST DÉCIDÉE PAR LE SERVEUR (routeIs), jamais par JavaScript.
  Un menu dont l'état dépend d'un script perd tout repère quand le script ne
  charge pas — et c'est le seul élément qui dit où l'on se trouve.

  ÉCRANS EN COURS DE CONSTRUCTION : une entrée dont le gabarit n'existe pas
  encore s'affiche atténuée et non cliquable, plutôt que de mener à une erreur
  500. Le test porte sur l'existence RÉELLE de la vue : l'atténuation disparaît
  d'elle-même à la livraison de l'écran, sans rien à modifier ici.
--}}
@php
    use App\Support\Navigation;

    /*
     | [identifiant, route, motif d'activation, vue attendue]
     |
     | L'IDENTIFIANT N'EST PLUS LE LIBELLÉ. Il servait aux deux : à écrire
     | l'intitulé du menu ET à choisir l'icône. Traduire l'intitulé aurait
     | donc fait retomber les onze icônes sur celle par défaut — sans
     | erreur, sans test rouge, onze entrées portant le même dessin.
     |
     | Le libellé vient maintenant de navigation.admin.*, l'icône de
     | l'identifiant. Les deux peuvent diverger sans se casser.
     */
    /* Ce que le dock du bas porte deja : ces entrees sont retirees du
       panneau SUR TELEPHONE UNIQUEMENT, pour qu'il ne les repete pas. */
    $routesDuDock = Navigation::routesDuDockAdmin();

    $sections = [
        'pilotage' => [
            ['vue-ensemble',  'admin.overview',        'admin.overview',      'admin.overview'],
            ['statistiques',  'admin.statistics',      'admin.statistics',    'admin.statistics'],
        ],
        'gestion' => [
            ['clients',       'admin.clients.index',   'admin.clients.*',     'admin.clients.index'],
            ['profils',       'admin.profiles.index',  'admin.profiles.*',    'admin.profiles.index'],
            ['paiements',     'admin.payments.index',  'admin.payments.*',    'admin.payments.index'],
            ['abonnements',   'admin.subscriptions',   'admin.subscriptions', 'admin.subscriptions'],
            ['cartes',        'admin.cards.index',     'admin.cards.*',       'admin.cards.index'],
        ],
        'configuration' => [
            ['modeles',       'admin.templates.index', 'admin.templates.*',   'admin.templates.index'],
            ['parametres',    'admin.settings',        'admin.settings*',     'admin.settings.index'],
            ['journal',       'admin.audit.index',     'admin.audit.*',       'admin.audit.index'],
        ],
        'systeme' => [
            ['etat-systeme',  'admin.system.health',   'admin.system.health', 'admin.system-health'],
        ],
    ];

    /* La correspondance identifiant → clé de traduction. Les tirets ne
       passent pas dans une clé de tableau PHP imbriquée : on les convertit
       une fois ici plutôt qu'à chaque ligne. */
    $cle = fn (string $id) => 'navigation.admin.'.str_replace('-', '_', $id);

    /* Un titre pose au-dessus du vide se lit comme une page cassee. */
    $sectionEntiereDansLeDock = [];

    foreach ($sections as $nom => $entrees) {
        $hors = array_filter($entrees, fn ($e) => ! Navigation::estDansLeDock($e[1], $routesDuDock));
        $sectionEntiereDansLeDock[$nom] = $hors === [];
    }
@endphp

<nav class="adm-nav" aria-label="{{ __('navigation.administration') }}">
    @foreach ($sections as $section => $entrees)
        <p @class([
            'adm-nav__titre',
            'adm-nav__titre--dans-le-dock' => $sectionEntiereDansLeDock[$section],
        ])>{{ __('navigation.sections.'.$section) }}</p>

        <ul class="adm-nav__liste">
            @foreach ($entrees as [$id, $route, $motif, $vue])
                @php $libelle = __($cle($id)); @endphp
                @php
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

    {{-- BAS DE MENU — deux entrées, détachées du reste.
         La déconnexion est un formulaire POST : en GET, une simple image
         distante suffirait à déconnecter l'administrateur. --}}
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
