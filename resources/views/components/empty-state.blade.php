{{--
  x-empty-state — l'état vide : dire ce qui manque, donner l'action qui le comble.

      <x-empty-state icon="profile" title="Aucun profil" message="…">
          <x-slot name="action"><x-button>Créer</x-button></x-slot>
      </x-empty-state>

  Un état vide n'est pas une page ratée : c'est le PREMIER écran d'un compte
  neuf, et celui de l'administration au lancement. Icône dans une tuile
  teintée, titre H3, une phrase, une action.

  icon : profile · payment · search · document (défaut) — ou tout nom de x-icone.
--}}
@props([
    'title' => 'Rien à afficher',
    'message' => null,
    'icon' => 'document',
])

@php
    $icone = ['profile' => 'id-card', 'payment' => 'credit-card', 'search' => 'search', 'document' => 'inbox'][$icon] ?? $icon;
@endphp

<div {{ $attributes->merge(['class' => 'etat-vide empty-state']) }}>
    <div class="etat-vide__icone" aria-hidden="true">
        <x-icone :name="$icone" />
    </div>

    <h3 class="etat-vide__titre">{{ $title }}</h3>

    @if ($message)
        <p class="etat-vide__texte">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="etat-vide__action">{{ $action }}</div>
    @endisset
</div>
