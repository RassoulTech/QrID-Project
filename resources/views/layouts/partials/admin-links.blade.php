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
    // [libellé, route, motif d'activation, vue attendue]
    $sections = [
        'Pilotage' => [
            ["Vue d'ensemble", 'admin.overview',        'admin.overview',      'admin.overview'],
            ['Statistiques',   'admin.statistics',      'admin.statistics',    'admin.statistics'],
        ],
        'Gestion' => [
            ['Clients',        'admin.clients.index',   'admin.clients.*',     'admin.clients.index'],
            ['Profils',        'admin.profiles.index',  'admin.profiles.*',    'admin.profiles.index'],
            ['Paiements',      'admin.payments.index',  'admin.payments.*',    'admin.payments.index'],
            ['Abonnements',    'admin.subscriptions',   'admin.subscriptions', 'admin.subscriptions'],
        ],
        'Configuration' => [
            ['Modèles',        'admin.templates.index', 'admin.templates.*',   'admin.templates.index'],
            ['Paramètres',     'admin.settings',        'admin.settings*',     'admin.settings.index'],
            ['Journal',        'admin.audit.index',     'admin.audit.*',       'admin.audit.index'],
        ],
        'Système' => [
            ['État système',   'admin.system.health',   'admin.system.health', 'admin.system-health'],
        ],
    ];
@endphp

<nav class="adm-nav" aria-label="Navigation de l'administration">
    @foreach ($sections as $titre => $entrees)
        <p class="adm-nav__titre">{{ $titre }}</p>

        <ul class="adm-nav__liste">
            @foreach ($entrees as [$libelle, $route, $motif, $vue])
                @php
                    $existe = Route::has($route) && View::exists($vue);
                    $actif = $existe && request()->routeIs($motif);
                @endphp

                <li>
                    @if ($existe)
                        <a href="{{ route($route) }}"
                           @class(['adm-nav__lien', 'is-active' => $actif])
                           @if ($actif) aria-current="page" @endif>
                            <x-admin-icon :name="$libelle" />
                            <span>{{ $libelle }}</span>
                        </a>
                    @else
                        <span class="adm-nav__lien is-muted" aria-disabled="true"
                              title="Écran en cours de construction">
                            <x-admin-icon :name="$libelle" />
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
            <x-admin-icon name="Retour" />
            <span>Retour à mon espace client</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="adm-nav__lien adm-nav__lien--action">
                <x-admin-icon name="Déconnexion" />
                <span>Déconnexion</span>
            </button>
        </form>
    </div>
</nav>
