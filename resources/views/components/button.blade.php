{{--
  x-button — le bouton du produit (direction A : rayon 10, 48px, 16px/600).

      <x-button :href="route('register')">Créer un compte</x-button>           principal (baobab)
      <x-button variant="secondary" :href="…">Voir un exemple</x-button>      secondaire (ivoire, filet)
      <x-button variant="quiet">Annuler</x-button>                            discret (lien de la taille d'un bouton)
      <x-button variant="danger">Supprimer</x-button>                         danger (l'irréversible seulement)
      <x-button variant="ivory">Prolonger</x-button>                          sur une couverture verte
      <x-button variant="outline-ivory">Historique</x-button>                 contour ivoire, sur couverture
      <x-button icon="download" size="sm">QR en PNG</x-button>                avec icône, petit (44px)

  Les noms historiques restent valables : `dark` = primary, `outline` =
  secondary, `light` = ivory, `ghost-light` = outline-ivory.

  Props : variant, href, type, size (sm|lg), block, disabled, icon (x-icone).
  L'état d'envoi (aria-busy) est posé par envoi-unique.js : une roue tourne,
  le bouton garde sa taille.
--}}
@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'submit',
    'size' => null,
    'block' => false,
    'disabled' => false,
    'icon' => null,
])

@php
    $alias = [
        'primary' => 'dark',
        'secondary' => 'outline',
        'outline-secondary' => 'outline',
        'ivory' => 'light',
        'outline-ivory' => 'ghost-light',
    ];
    $classe = $alias[$variant] ?? $variant;

    $classes = 'btn-pill btn-'.$classe
        .($size === 'sm' ? ' btn-sm-pill' : '')
        .($size === 'lg' ? ' btn-lg' : '')
        .($block ? ' btn-block' : '');
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<x-icone :name="$icon" />@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<x-icone :name="$icon" />@endif{{ $slot }}</button>
@endif
