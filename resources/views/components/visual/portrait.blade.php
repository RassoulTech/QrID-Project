{{--
  x-visual.portrait — ILLUSTRATION CSS tenant la place d'une photographie.

      <x-visual.portrait name="Awa Ndiaye" role="Architecte" />
      <x-visual.portrait variant="scene" />

  CHOIX ASSUMÉ ET VALIDÉ : les maquettes montrent des photographies ; elles
  sont remplacées par une illustration construite en CSS. Aucun fichier
  externe, aucune licence à gérer, aucun visage réel engagé dans l'image du
  produit. Ce n'est donc PAS une reproduction à l'identique de ces blocs.

  Variantes :
    portrait (défaut) — buste stylisé sur fond dégradé
    scene             — bloc de scène (plusieurs silhouettes)
--}}
@props([
    'variant' => 'portrait',
    'name' => null,
    'role' => null,
])

<div {{ $attributes->merge(['class' => 'av-portrait av-portrait--'.$variant]) }}>
    <div class="av-portrait__image">
        @if ($variant === 'scene')
            {{-- Trois silhouettes décalées : une scène de bureau, sans visage. --}}
            <span class="av-portrait__figure av-portrait__figure--a"></span>
            <span class="av-portrait__figure av-portrait__figure--b"></span>
            <span class="av-portrait__figure av-portrait__figure--c"></span>
            <span class="av-portrait__desk"></span>
        @else
            <span class="av-portrait__head"></span>
            <span class="av-portrait__bust"></span>
        @endif
    </div>

    @if ($name || $role)
        <div class="av-portrait__meta">
            @if ($name)
                <span class="av-portrait__name">{{ $name }}</span>
            @endif
            @if ($role)
                <span class="av-portrait__role">{{ $role }}</span>
            @endif
        </div>
    @endif
</div>
