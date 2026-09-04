{{--
  x-card-duo — les deux faces, avec la permutation.

      <x-card-duo :profile="$profile" />

  ═══════════════════════════════════════════════════════════════════════
  SANS JAVASCRIPT, LES DEUX FACES S'AFFICHENT L'UNE SOUS L'AUTRE
  ═══════════════════════════════════════════════════════════════════════
  Aucune face n'est masquée dans le HTML, et le bouton porte [hidden]. Si
  le script ne se charge pas — 3G coupée, module en erreur — le client voit
  TOUT : recto, verso, les deux QR. Rien ne disparaît.

  C'est le module qui ajoute .is-flippable, et c'est cette classe seule qui
  autorise le CSS à empiler les faces. L'ordre est important : la mise en
  scène 3D ne peut pas précéder la certitude qu'on saura en sortir.

  Props : profile, variant (facultatif : la variante du profil sinon)
--}}
@props(['profile', 'variant' => null])

<div {{ $attributes->merge(['class' => 'card-duo']) }} data-card-duo>
    <div class="card-duo__scene">
        <x-card face="recto" :profile="$profile" :variant="$variant" class="card-duo__face card-duo__face--recto" />
        <x-card face="verso" :profile="$profile" :variant="$variant" class="card-duo__face card-duo__face--verso" />
    </div>

    <div class="card-duo__commande" data-card-duo-commande hidden>
        {{-- LES DEUX ÉTIQUETTES VOYAGENT AVEC LE BOUTON.
             Elles étaient écrites en dur dans le module JavaScript : un
             client anglophone qui retournait sa carte voyait le bouton
             repasser en français. Un fichier de traduction n'est pas
             lisible depuis un module JS — le HTML est le seul endroit où
             les deux langues se rejoignent. --}}
        <button type="button" class="card-duo__bouton" data-card-duo-toggle aria-pressed="false"
                data-libelle-verso="{{ __('card.voir_verso') }}"
                data-libelle-recto="{{ __('card.voir_recto') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/>
                <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/>
            </svg>
            <span data-card-duo-label>{{ __('card.voir_verso') }}</span>
        </button>
    </div>
</div>
