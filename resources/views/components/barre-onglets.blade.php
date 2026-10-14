{{--
  x-barre-onglets — LA NAVIGATION DU TÉLÉPHONE : une barre ANCRÉE en bas.

      <x-barre-onglets :entrees="[
          ['url' => route('dashboard'), 'actif' => ['dashboard'], 'icone' => 'grille', 'libelle' => __('…')],
          …
      ]" jeu="client" />

  ═══════════════════════════════════════════════════════════════════════
  CE QU'ELLE REMPLACE, ET POURQUOI
  ═══════════════════════════════════════════════════════════════════════
  Le dock en pilule flottait au-dessus du contenu, avec un flou d'arrière-
  plan : il recouvrait ce qui passait dessous, et le flou coûtait cher à
  chaque image de défilement. La barre est ANCRÉE au bord, OPAQUE, sans
  flou, séparée du contenu par un filet, et le corps de page lui réserve sa
  hauteur : elle ne recouvre jamais la fin du contenu.

  ═══════════════════════════════════════════════════════════════════════
  CINQ ENTRÉES AU PLUS, ICÔNE + LIBELLÉ
  ═══════════════════════════════════════════════════════════════════════
  À 320px, cinq entrées laissent 64px chacune : une icône et un libellé
  court de huit caractères au plus. Une icône seule n'est pas comprise du
  premier coup, et le premier coup est le seul qui compte.

  ═══════════════════════════════════════════════════════════════════════
  L'ENTRÉE ACTIVE SE LIT AU REPOS — fond ET couleur, et aria-current
  ═══════════════════════════════════════════════════════════════════════
  Jamais la seule couleur, jamais un effet de survol (il n'y en a pas au
  doigt). L'état est décidé par le SERVEUR (routeIs) : sans JavaScript, la
  barre sait toujours où l'on est.

  « PLUS » (administration seulement) ouvre la colonne des autres rubriques.
  C'est un LIEN vers l'ancre de la colonne : avec JavaScript, Bootstrap
  l'ouvre en panneau ; sans, `:target` l'affiche quand même.
--}}
@props([
    'entrees' => [],
    'jeu' => 'client',
    'panneau' => null,
])

@php
    /* CINQ AU MAXIMUM, coupées ici plutôt que dans chaque appelant : une
       règle qu'on peut contourner depuis la vue n'est pas une règle. */
    $entrees = array_slice($entrees, 0, $panneau ? 4 : 5);
@endphp

<nav class="onglets-bas" aria-label="{{ __('navigation.dock.aria') }}">
    <ul class="onglets-bas__liste">
        @foreach ($entrees as $entree)
            @php
                $estActif = request()->routeIs(...(array) ($entree['actif'] ?? $entree['route']));
                $compte = $entree['compte'] ?? null;
            @endphp

            <li class="onglets-bas__item">
                <a href="{{ $entree['url'] ?? route($entree['route']) }}"
                   @class(['onglets-bas__lien', 'is-actif' => $estActif])
                   @if ($estActif) aria-current="page" @endif>
                    <span class="onglets-bas__icone">
                        @if ($jeu === 'admin')
                            <x-admin-icon :name="$entree['icone']" />
                        @else
                            <x-client-icon :name="$entree['icone']" />
                        @endif

                        {{-- UNE PASTILLE SEULEMENT SI LE NOMBRE APPELLE UNE
                             ACTION — et toujours un chiffre, pas une couleur. --}}
                        @if ($compte)
                            <span class="onglets-bas__pastille" aria-hidden="true">{{ $compte > 99 ? '99+' : $compte }}</span>
                            <span class="visually-hidden">{{ trans_choice('navigation.dock.en_attente', $compte, ['compte' => $compte]) }}</span>
                        @endif
                    </span>
                    <span class="onglets-bas__libelle">{{ $entree['libelle'] }}</span>
                </a>
            </li>
        @endforeach

        @if ($panneau)
            <li class="onglets-bas__item">
                <a href="#{{ $panneau }}" class="onglets-bas__lien"
                   data-bs-toggle="offcanvas" data-bs-target="#{{ $panneau }}"
                   role="button" aria-controls="{{ $panneau }}">
                    <span class="onglets-bas__icone"><x-icone name="menu" /></span>
                    <span class="onglets-bas__libelle">{{ __('navigation.dock.plus') }}</span>
                </a>
            </li>
        @endif
    </ul>
</nav>
