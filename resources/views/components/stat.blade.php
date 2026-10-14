{{--
  x-stat — l'INDICATEUR CHIFFRÉ : gros chiffres tabulaires (signature 5).

      <x-stat label="Vues" value="1 248" icon="eye" trend="up" trend-value="+293 en 7 jours" />
      <x-stat label="Encaissé · 30 jours" value="266 000" unit="FCFA" hint="76 paiements réussis" />

  Le chiffre en 820, tabulaire : deux indicateurs voisins s'alignent chiffre
  à chiffre. La tendance porte une FLÈCHE et un signe — la couleur n'est
  jamais seule à dire « hausse » ou « baisse ».

  Aucun chiffre n'est inventé ici : le composant affiche ce qu'on lui donne.
--}}
@props([
    'label',
    'value',
    'unit' => null,
    'icon' => null,
    'trend' => null,
    'trendValue' => null,
    'hint' => null,
])

@php
    $sens = ['up' => ['is-hausse', 'arrow-up-right'], 'down' => ['is-baisse', 'arrow-up-right'], 'flat' => ['', 'arrow-right']][$trend] ?? ['', 'arrow-right'];
@endphp

<div {{ $attributes->merge(['class' => 'indic stat']) }}>
    <span class="indic__libelle">@if ($icon)<x-icone :name="$icon" />@endif{{ $label }}</span>
    <span class="indic__valeur">{{ $value }}@if ($unit)<small>{{ $unit }}</small>@endif</span>

    @if ($trend && $trendValue)
        <span @class(['indic__tendance', $sens[0]])>
            <x-icone :name="$sens[1]" :class="$trend === 'down' ? 'indic__fleche--bas' : ''" />
            {{ $trendValue }}
        </span>
    @endif

    @if ($hint)
        <span class="indic__aide">{{ $hint }}</span>
    @endif
</div>
