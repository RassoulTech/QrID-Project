{{--
  x-visual.stat-card — petite carte de statistiques, avec son mini histogramme.

      <x-visual.stat-card value="1.2k" label="Vues du mois" position="bas-droite" />

  Le chiffre est ILLUSTRATIF, pas une mesure : ces écrans ne lisent rien en
  base. Il ne doit jamais être présenté comme une statistique réelle du produit.
--}}
@props([
    'value' => '1.2k',
    'label' => 'Vues du mois',
    'bars' => [40, 62, 48, 78, 96],
    'position' => null,
])

@php
    $positions = [
        'haut-gauche' => 'av-stat--hg',
        'haut-droite' => 'av-stat--hd',
        'bas-gauche' => 'av-stat--bg',
        'bas-droite' => 'av-stat--bd',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'av-stat '.($positions[$position] ?? '')]) }}>
    <span class="av-stat__value">{{ $value }}</span>
    <span class="av-stat__label">{{ $label }}</span>

    <span class="av-stat__chart">
        @foreach ($bars as $h)
            <span class="av-stat__bar" style="height:{{ max(12, min(100, (int) $h)) }}%"></span>
        @endforeach
    </span>
</div>
