{{--
  x-button — bouton pilule.

  <x-button variant="dark" :href="route('register')">Créer un compte</x-button>
  <x-button variant="outline" :href="route('profile.demo')">Voir un exemple</x-button>
  <x-button variant="light" :block="true">Choisir l'Annuel</x-button>

  Variantes : dark (défaut) · outline · light
  Props     : variant, href, type, size (sm), block, disabled
--}}
@props([
    'variant' => 'dark',
    'href' => null,
    'type' => 'submit',
    'size' => null,
    'block' => false,
    'disabled' => false,
])

@php
    $classes = 'btn-pill btn-'.$variant
        .($size === 'sm' ? ' btn-sm-pill' : '')
        .($block ? ' btn-block' : '');
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
