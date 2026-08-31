{{--
  x-dock — LA NAVIGATION PERMANENTE SUR TÉLÉPHONE.

      <x-dock :entrees="[
          ['route' => 'dashboard', 'icone' => 'grille', 'libelle' => __('...')],
          ...
      ]" jeu="client" />

  ═══════════════════════════════════════════════════════════════════════
  CE QU'IL REMPLACE, ET POURQUOI
  ═══════════════════════════════════════════════════════════════════════
  La navigation se repliait derrière un bouton hamburger sous 992px. Deux
  conséquences, toutes deux mesurables :

  · CHANGER D'ÉCRAN DEMANDAIT DEUX GESTES — ouvrir le panneau, puis
    choisir. Sur un produit dont l'usage se fait debout, une main occupée
    par une carte, c'est un geste de trop.
  · RIEN NE DISAIT OÙ L'ON ÉTAIT. Le panneau fermé, l'entrée active était
    invisible. On savait ce qu'on regardait, jamais où l'on se trouvait.

  Le dock rend les deux : la destination est à un geste, et la position
  est lisible en permanence.

  ═══════════════════════════════════════════════════════════════════════
  CINQ ENTRÉES, PAS SIX
  ═══════════════════════════════════════════════════════════════════════
  À 320px, cinq entrées laissent 57px chacune once les marges retirées —
  de quoi porter une icône et un libellé lisible. Six n'en laissent que
  48 : le libellé se tronque, et une icône seule n'est pas comprise.

  L'administration a douze destinations. Les cinq premières entrent dans
  le dock, le reste demeure dans le panneau : un dock qui déborde n'est
  plus un dock, c'est une barre de défilement.

  ═══════════════════════════════════════════════════════════════════════
  L'ENTRÉE ACTIVE SE LIT AU REPOS
  ═══════════════════════════════════════════════════════════════════════
  Loi 7. Elle est distinguée par son FOND et par la COULEUR de son
  contenu — jamais par la seule surélévation, qu'on ne perçoit pas sur un
  écran mat en plein soleil, et jamais par un effet qui demanderait un
  survol qui n'existe pas au doigt.

  `aria-current="page"` le dit aussi aux lecteurs d'écran, qui ne voient
  ni fond ni couleur.

  ═══════════════════════════════════════════════════════════════════════
  L'ÉTAT ACTIF EST DÉTERMINÉ CÔTÉ SERVEUR
  ═══════════════════════════════════════════════════════════════════════
  `request()->routeIs()` et non un script. Sans JavaScript, le dock reste
  complet et sait toujours où l'on est.
--}}
@props([
    'entrees' => [],
    'jeu' => 'client',
])

@php
    /* CINQ AU MAXIMUM, coupées ici plutôt que dans chaque appelant : une
       règle qu'on peut contourner depuis la vue n'est pas une règle. */
    $entrees = array_slice($entrees, 0, 5);
@endphp

<nav class="dock" aria-label="{{ __('navigation.dock.aria') }}">
    <ul class="dock__liste">
        @foreach ($entrees as $entree)
            @php
                $motif = $entree['actif'] ?? $entree['route'];
                $estActif = request()->routeIs($motif);
                $compte = $entree['compte'] ?? null;
            @endphp

            <li class="dock__item">
                <a
                    href="{{ route($entree['route']) }}"
                    class="dock__lien @if ($estActif) is-actif @endif"
                    @if ($estActif) aria-current="page" @endif
                >
                    <span class="dock__icone">
                        @if ($jeu === 'admin')
                            <x-admin-icon :name="$entree['icone']" />
                        @else
                            <x-client-icon :name="$entree['icone']" />
                        @endif

                        {{-- UNE PASTILLE NE S'AFFICHE QUE SI LE NOMBRE
                             APPELLE UNE ACTION. Un compteur qui affiche
                             « 0 », ou qui compte pour informer, est du
                             bruit : on apprend à ne plus le voir, et le
                             jour où il compte vraiment on ne le voit pas
                             non plus. --}}
                        @if ($compte)
                            <span class="dock__pastille" aria-hidden="true">
                                {{ $compte > 99 ? '99+' : $compte }}
                            </span>
                            <span class="visually-hidden">
                                {{ trans_choice('navigation.dock.en_attente', $compte, ['compte' => $compte]) }}
                            </span>
                        @endif
                    </span>

                    {{-- LE LIBELLÉ EST TOUJOURS LÀ. Une icône seule n'est
                         pas comprise du premier coup, et le premier coup
                         est le seul qui compte. --}}
                    <span class="dock__libelle">{{ $entree['libelle'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
