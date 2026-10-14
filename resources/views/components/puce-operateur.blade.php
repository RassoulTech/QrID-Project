{{--
  x-puce-operateur — la pastille d'un opérateur de paiement, avec son NOM.

      <x-puce-operateur methode="wave" />            puce : point coloré + « Wave »
      <x-puce-operateur methode="orange_money" :puce="false" />   point + nom, sans cadre (tableau)

  Le point porte la couleur officielle (jeton) ; le NOM porte l'information.
  La couleur n'est jamais seule : un lecteur qui ne distingue pas l'orange
  du rouge lit « Orange Money » et « Free Money ».

  Les méthodes et leurs noms viennent de App\Models\Payment::METHODS.
--}}
@props(['methode', 'puce' => true])

@php
    $nom = \App\Models\Payment::METHODS[$methode] ?? $methode;
@endphp

<span {{ $attributes->merge(['class' => $puce ? 'puce-op' : 'puce-op puce-op--nue']) }}>
    <span class="puce-op__point puce-op__point--{{ $methode }}" aria-hidden="true"></span>
    {{ $nom }}
</span>
