{{--
  x-brand — la marque : le monogramme et le nom.

      <x-brand />                                    taille moyenne, lien vers l'accueil
      <x-brand size="sm" :href="route('dashboard')" />
      <x-brand tone="light" />                       sur une surface verte (couverture)
      <x-brand :link="false" />                      dans un titre, sans lien
      <x-brand :words="false" :link="false" />       le monogramme seul
      <x-brand size="xl" :words="false" />           le monogramme en grand (planche)

  LE COMPOSANT UNIQUE DE LA MARQUE. Barres, colonne, pied de page, pages
  d'erreur, design-system : tout passe par ici. Un second dessin du logo
  quelque part serait un second logo, qui divergerait au premier ajustement.

  LE MONOGRAMME (signature 3 de la direction A) : le carré de repérage d'un
  QR Code devient un Q — l'anneau et son œil en vert, la queue en or. Il
  reste lisible à 16px, en favicon comme en tête de colonne.

  SVG EN LIGNE, et non une image : il prend la couleur de la surface
  (`currentColor` pour l'anneau, `--marque-queue` pour la queue) et bascule
  avec le thème sans second fichier. Aucune couleur n'est écrite ici — elles
  viennent de `_brand.scss`.

  LE NOM n'est jamais mis en capitales : la casse d'APP_NAME fait foi.
--}}
@props([
    'href' => null,
    'link' => true,
    'size' => 'md',
    'tone' => 'dark',
    'tagline' => null,
    'words' => true,
])

@php
    $nom = config('app.name');
    $balise = $link ? 'a' : 'span';
    $cible = $href ?? route('home');
@endphp

<{{ $balise }}
    @if ($link) href="{{ $cible }}" @endif
    {{ $attributes->merge(['class' => 'brand brand--'.$size.' brand--'.$tone]) }}>

    {{-- aria-hidden : le nom suit. Sans le nom, c'est le texte masqué
         ci-dessous qui porte l'identité. --}}
    <svg class="brand__mark" viewBox="0 0 40 40" width="34" height="34" aria-hidden="true" focusable="false">
        <rect x="3.5" y="3.5" width="29" height="29" rx="8" fill="none" stroke="currentColor" stroke-width="5"/>
        <rect x="12.5" y="12.5" width="11" height="11" rx="2.5" fill="currentColor"/>
        <path class="brand__queue" d="M27.5 27.5 36 36" fill="none" stroke-width="5.5" stroke-linecap="round"/>
    </svg>

    @if ($words)
        <span class="brand__words">
            <span class="brand__name">{{ $nom }}</span>
            @if ($tagline)
                <span class="brand__tagline">{{ $tagline }}</span>
            @endif
        </span>
    @else
        <span class="visually-hidden">{{ $nom }}</span>
    @endif
</{{ $balise }}>
