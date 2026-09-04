{{--
  MENU DE L'ESPACE CLIENT — et il sert DEUX surfaces différentes.

  ═══════════════════════════════════════════════════════════════════════════
  SUR ORDINATEUR : la colonne de gauche. C'est la navigation, complète.
  SUR TÉLÉPHONE  : le panneau « Plus », ouvert depuis le dock du bas.
  ═══════════════════════════════════════════════════════════════════════════

  Et c'est là que se trouvait le défaut, visible à l'écran : le dock portait
  Tableau de bord, Profil, Carte et Statistiques — et « Plus » rouvrait
  exactement les quatre mêmes. Quatre entrées sur cinq faisaient double
  emploi. On demandait au pouce de choisir entre deux chemins vers la même
  page, et l'un des deux demandait un geste de plus.

  « Plus » ne peut contenir que ce qui ne tient PAS dans le dock. Sinon ce
  n'est pas un menu, c'est une copie.

  ═══════════════════════════════════════════════════════════════════════════
  COMMENT LES DEUX SURFACES DIVERGENT SANS QUE LE FICHIER SOIT DÉDOUBLÉ
  ═══════════════════════════════════════════════════════════════════════════
  Le serveur ne connaît pas la largeur de l'écran : il ne peut pas rendre
  deux listes différentes. C'est donc la feuille de style qui tranche, sur
  la MÊME rupture que celle où le dock apparaît et disparaît (`lg`) :

    · sous lg — le dock existe : ses quatre destinations sont retirées du
      panneau, `display: none`, ce qui les sort aussi de l'arbre
      d'accessibilité. Elles n'ont pas disparu : elles sont dans le dock,
      à un geste, et le lecteur d'écran les y trouve.

    · à partir de lg — le dock n'existe plus : la colonne redevient la
      navigation entière, et rien n'est masqué.

  Les routes du dock ne sont pas recopiées ici : elles viennent de
  App\Support\Navigation, que la coque lit aussi pour construire le dock.
  Recopiées, elles finiraient par diverger — et la divergence est
  précisément le défaut qu'on corrige.

  « Mon profil » reste active pendant TOUT le parcours de création : sans
  cela, le menu perdrait son repère au milieu des trois étapes.
--}}
@php
    use App\Support\Navigation;

    $aProfil = Auth::user()?->profile !== null;

    $profilActif = request()->routeIs('profil.index')
        || request()->routeIs('profile.create.*')
        || request()->routeIs('profile.edit')
        || request()->routeIs('profile.preview');

    $routesDuDock = Navigation::routesDuDockClient();

    /*
     | Chaque entrée porte le nom de route que le DOCK utiliserait pour elle.
     |
     | Ce n'est pas toujours l'adresse du lien : « Mon profil » mène à la
     | première étape de création tant qu'il n'y a pas de profil, alors que
     | le dock pointe toujours vers `profil.index`. C'est la même destination
     | aux yeux de la personne, et c'est ce qui compte pour décider si le
     | panneau la répète.
     */
    $sections = [
        'pilotage' => [
            ['dashboard', __('navigation.client.tableau_de_bord'), route('dashboard'), request()->routeIs('dashboard'), 'grille'],
            ['statistiques', __('navigation.client.statistiques'), route('statistiques'), request()->routeIs('statistiques'), 'courbe'],
        ],
        'ma_carte' => [
            ['profil.index', __('navigation.client.mon_profil'), $aProfil ? route('profil.index') : route('profile.create.step1'), $profilActif, 'personne'],
            ['carte.qr', __('navigation.client.mon_qr'), route('carte.qr'), request()->routeIs('carte.qr'), 'qr'],
        ],
        'compte' => [
            // Mène à l'écran des formules, qui sert aussi bien la première
            // souscription que le renouvellement, selon l'état du compte.
            ['abonnement.paiement', __('navigation.client.mon_abonnement'), route('abonnement.paiement'), request()->routeIs('abonnement.*'), 'carte-bancaire'],

            // « Mon compte » n'existait que derrière l'avatar, en haut à
            // droite — le coin le plus éloigné du pouce. Le panneau du bas
            // est l'endroit où on le cherche sur un téléphone.
            ['compte.edit', __('navigation.coque.mon_compte'), route('compte.edit'), request()->routeIs('compte.*'), 'engrenage'],
        ],
    ];

    /*
     | UNE SECTION DONT TOUTES LES ENTRÉES SONT DANS LE DOCK N'A PLUS DE
     | TITRE À AFFICHER SUR TÉLÉPHONE.
     |
     | Sans ce calcul, « Pilotage » et « Ma carte » resteraient posés au-dessus
     | du vide : deux intitulés sans rien dessous, ce qui se lit comme une
     | page cassée plutôt que comme un menu court.
     */
    $sectionEntiereDansLeDock = [];

    foreach ($sections as $nom => $entrees) {
        $hors = array_filter($entrees, fn ($e) => ! Navigation::estDansLeDock($e[0], $routesDuDock));
        $sectionEntiereDansLeDock[$nom] = $hors === [];
    }
@endphp

{{-- x-client-icon reçoit un identifiant propre (« grille », « qr »), pas le
     libellé : le menu peut être traduit sans que les icônes ne bougent. --}}
<nav class="adm-nav" aria-label="{{ __('navigation.principale') }}">
    @foreach ($sections as $section => $entrees)
        <p @class([
            'adm-nav__titre',
            'adm-nav__titre--dans-le-dock' => $sectionEntiereDansLeDock[$section],
        ])>{{ __('navigation.sections.'.$section) }}</p>

        <ul class="adm-nav__liste">
            @foreach ($entrees as [$route, $libelle, $url, $actif, $icone])
                <li @class(['adm-nav__item--dans-le-dock' => Navigation::estDansLeDock($route, $routesDuDock)])>
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
         Jamais dans le dock, sur aucune largeur : ce sont les deux seules
         entrées qui ne mènent pas à un écran du produit.
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
