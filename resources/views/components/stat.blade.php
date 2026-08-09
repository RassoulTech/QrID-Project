{{--
  x-stat — indicateur chiffré.

  <x-stat label="Vues du profil" value="1 248" trend="up" trend-value="+12 %" />
  <x-stat label="Revenus" value="25 000 FCFA" hint="Ce mois-ci" />

  Props : label, value, trend (up | down | flat), trendValue, hint
  Les montants sont formatés par l'appelant : « 25 000 FCFA », entiers,
  jamais de décimales.
--}}
@props([
    'label',
    'value',
    'trend' => null,
    'trendValue' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'stat']) }}>
    <div class="stat__label">{{ $label }}</div>
    <div class="stat__value">{{ $value }}</div>

    @if ($trend && $trendValue)
        <div class="stat__trend stat__trend--{{ $trend }}">
            <span aria-hidden="true">
                @if ($trend === 'up') ↑ @elseif ($trend === 'down') ↓ @else → @endif
            </span>
            {{ $trendValue }}
        </div>
    @endif

    @if ($hint)
        <div class="text-secondary small mt-1">{{ $hint }}</div>
    @endif
</div>
