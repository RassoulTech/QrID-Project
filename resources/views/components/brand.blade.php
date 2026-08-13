{{--
  x-brand — la marque : pastille monogramme + nom.

      <x-brand />                                    taille moyenne, lien vers l'accueil
      <x-brand size="sm" :href="route('dashboard')" />
      <x-brand tone="light" tagline="Identité numérique" />
      <x-brand :link="false" />                      dans un titre, sans lien
      <x-brand :words="false" :link="false" />       le carré seul

  LE COMPOSANT UNIQUE DE LA MARQUE. Navbar, menu latéral, cartes PVC,
  design-system : tout passe par ici. Un second dessin de logo quelque part
  serait un second logo, qui divergerait au premier ajustement.

  LE MONOGRAMME reprend la première lettre de chaque mot du nom : QR → Q,
  ID → I. Il reste donc juste si le nom change, puisqu'il est calculé et non
  écrit en dur.

  LE NOM n'est jamais mis en capitales. « QRID » tout en majuscules se lit mal
  et alourdit la marque ; la casse configurée dans APP_NAME fait foi, et le
  contraste entre la pastille et le mot suffit à porter l'identité.

  `words="false"` NE REND QUE LE CARRÉ. Utile là où le nom figure déjà à
  proximité — le verso de la carte l'écrit en grand juste en dessous, et
  l'afficher deux fois côte à côte affaiblirait les deux.
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

    /*
     | Le monogramme vient de App\Support\Marque, et non d'un calcul écrit ici.
     | Le gabarit d'impression a besoin des mêmes lettres : deux calculs
     | finiraient par diverger, et la divergence se constaterait sur des cartes
     | déjà sorties de l'imprimerie.
     */
    $lettres = \App\Support\Marque::monogramme($nom);

    $balise = $link ? 'a' : 'span';
    $cible = $href ?? route('home');
@endphp

<{{ $balise }}
    @if ($link) href="{{ $cible }}" @endif
    {{ $attributes->merge(['class' => 'brand brand--'.$size.' brand--'.$tone]) }}>

    {{-- aria-hidden sur le carré : c'est une abréviation décorative du nom
         qui suit. Sans le nom, en revanche, il devient le SEUL porteur de
         l'identité et doit être annoncé. --}}
    <span class="brand__mark" @if ($words) aria-hidden="true" @endif>{{ $lettres }}</span>

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
