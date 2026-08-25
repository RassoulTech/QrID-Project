{{--
  x-language-toggle — le sélecteur de langue, en menu déroulant.

      <x-language-toggle />

  ═══════════════════════════════════════════════════════════════════════
  POURQUOI UN MENU, ET PLUS UNE BASCULE
  ═══════════════════════════════════════════════════════════════════════
  Le bouton précédent affichait « FR » et basculait vers l'autre langue au
  clic. Il posait deux problèmes que seule une liste résout.

  Il ne DISAIT PAS ce qu'il ferait. « FR » ne dit pas s'il s'agit de la
  langue affichée ou de celle qu'on obtiendrait — le title le précisait,
  mais un title ne se lit ni au doigt ni d'un coup d'œil.

  Il ne MONTRAIT PAS les langues disponibles. Un visiteur anglophone devant
  « FR » ne sait pas si l'anglais existe : il faut cliquer pour l'apprendre,
  et cliquer c'est déjà avoir changé de page.

  Le menu montre les deux langues, écrites chacune DANS SA PROPRE LANGUE —
  « Français », « English » — et coche celle qui est active. On voit ce
  qu'on a et ce qu'on peut avoir, sans rien deviner.

  ═══════════════════════════════════════════════════════════════════════
  <details> ET NON UN COMPOSANT À SCRIPT
  ═══════════════════════════════════════════════════════════════════════
  <details>/<summary> ouvre et ferme nativement : au clic, au doigt, à la
  touche Entrée et à la barre d'espace, sans une ligne de JavaScript. Le
  module langue.js n'ajoute que le confort — fermer en cliquant ailleurs ou
  avec Échap — et son absence ne casse rien.

  POST et non GET : changer de langue MODIFIE un état. Un lien serait suivi
  par les robots d'indexation et les préchargeurs de navigateur, qui
  basculeraient la langue de visiteurs n'ayant rien demandé.

  Accessible aux invités comme aux comptes : la route est hors du groupe
  « auth ». Pour un compte, la préférence est écrite en base — c'est elle qui
  sert aussi aux e-mails, qui partent hors session et n'ont aucun cookie à
  lire ; pour un invité, dans un cookie d'un an.
--}}
@php
    /* LA LANGUE APPLIQUÉE, pas la chaîne de décision. Le middleware a déjà
       tranché ; refaire le calcul ici donnerait une AUTRE réponse — la vue
       n'a pas la requête sous la main, donc pas la négociation sur
       Accept-Language. Le menu cocherait « Français » sur une page rendue
       en anglais. */
    $courante = App\Support\Langue::active();
@endphp

<details {{ $attributes->merge(['class' => 'langue']) }} data-langue>
    <summary class="langue__declencheur"
             title="{{ __('navigation.langue.changer') }}"
             aria-label="{{ __('navigation.langue.courante', ['langue' => App\Support\Langue::libelle($courante)]) }}">
        <svg class="langue__globe" width="15" height="15" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
        </svg>

        <span class="langue__code">{{ App\Support\Langue::code($courante) }}</span>

        <svg class="langue__chevron" width="11" height="11" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </summary>

    {{-- Le formulaire EST le menu : chaque langue est un bouton d'envoi qui
         porte sa propre valeur. Aucun état intermédiaire à gérer, aucun
         champ caché à tenir à jour. --}}
    <form method="POST" action="{{ route('preferences.langue') }}" class="langue__menu">
        @csrf

        @foreach (App\Support\Langue::libelles() as $code => $libelle)
            <button type="submit" name="langue" value="{{ $code }}"
                    class="langue__option"
                    @if ($code === $courante) aria-current="true" @endif>
                {{-- Le nom de la langue n'est JAMAIS traduit : un anglophone
                     perdu dans une page française doit reconnaître
                     « English », pas lire « Anglais ». --}}
                <span class="langue__nom">{{ $libelle }}</span>

                @if ($code === $courante)
                    <svg class="langue__coche" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <path d="m5 13 4 4L19 7"/>
                    </svg>
                @endif
            </button>
        @endforeach
    </form>
</details>
