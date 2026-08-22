{{--
  x-drapeau — le drapeau d'un pays, dessiné en SVG.

      <x-drapeau code="SN" />
      <x-drapeau code="CI" :taille="26" />

  L'ÉMOJI DRAPEAU N'EST PAS UNE OPTION : Windows ne sait pas le composer et
  affiche « SN » dans un rectangle gris. Voir App\Support\Drapeaux pour le
  détail du tracé.

  ═══════════════════════════════════════════════════════════════════════
  UNE PLANCHE, DES RÉFÉRENCES
  ═══════════════════════════════════════════════════════════════════════
  Les trente-cinq tracés sont posés UNE FOIS par page, en <symbol>, et
  chaque drapeau visible n'est qu'un <use> qui pointe dedans. Deux raisons :
  trois champs téléphone sur un même écran ne répètent pas le tracé, et le
  sélecteur d'indicatif peut changer de drapeau en changeant une seule
  référence — sans rien redemander au serveur.

  aria-hidden : le nom du pays est toujours écrit à côté, en toutes lettres.
  Un lecteur d'écran qui annoncerait « drapeau du Sénégal, Sénégal (+221) »
  répéterait la même information deux fois.
--}}
@props(['code', 'taille' => 22])

@once
    <svg class="drapeaux-planche" width="0" height="0" aria-hidden="true" focusable="false">
        {!! \App\Support\Drapeaux::sprite() !!}
    </svg>
@endonce

@php
    $reference = \App\Support\IndicatifsPays::existe($code)
        ? 'drapeau-'.mb_strtoupper($code)
        : 'drapeau-inconnu';
@endphp

<svg viewBox="0 0 3 2"
     width="{{ $taille }}" height="{{ round($taille * 2 / 3) }}"
     {{ $attributes->merge(['class' => 'drapeau']) }}
     aria-hidden="true" focusable="false">
    <use href="#{{ $reference }}" />
</svg>
