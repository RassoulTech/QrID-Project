{{--
  x-brand — la marque : pastille monogramme + nom.

      <x-brand />                                    taille moyenne, lien vers l'accueil
      <x-brand size="sm" :href="route('dashboard')" />
      <x-brand tone="light" tagline="Identité numérique" />
      <x-brand :link="false" />                      dans un titre, sans lien

  LE MONOGRAMME reprend la première lettre de chaque mot du nom : QR → Q,
  ID → I. Il reste donc juste si le nom change, puisqu'il est calculé et non
  écrit en dur.

  LE NOM n'est jamais mis en capitales. « QRID » tout en majuscules se lit mal
  et alourdit la marque ; la casse configurée dans APP_NAME fait foi, et le
  contraste entre la pastille et le mot suffit à porter l'identité.
--}}
@props([
    'href' => null,
    'link' => true,
    'size' => 'md',
    'tone' => 'dark',
    'tagline' => null,
])

@php
    $nom = config('app.name');

    /*
     | Monogramme calculé : première lettre de chaque mot, deux au maximum.
     | « QrID » n'ayant qu'un mot, on prend ses deux capitales — ce qui donne
     | bien Q et I. Un nom en deux mots donnerait leurs deux initiales.
     */
    $mots = preg_split('/[\s\-_]+/', $nom, -1, PREG_SPLIT_NO_EMPTY) ?: [$nom];

    if (count($mots) >= 2) {
        $lettres = mb_substr($mots[0], 0, 1).mb_substr($mots[1], 0, 1);
    } else {
        preg_match_all('/\p{Lu}/u', $nom, $capitales);
        $lettres = implode('', array_slice($capitales[0] ?: [mb_substr($nom, 0, 1)], 0, 2));
    }

    $balise = $link ? 'a' : 'span';
    $cible = $href ?? route('home');
@endphp

<{{ $balise }}
    @if ($link) href="{{ $cible }}" @endif
    {{ $attributes->merge(['class' => 'brand brand--'.$size.' brand--'.$tone]) }}>

    <span class="brand__mark" aria-hidden="true">{{ $lettres }}</span>

    <span class="brand__words">
        <span class="brand__name">{{ $nom }}</span>
        @if ($tagline)
            <span class="brand__tagline">{{ $tagline }}</span>
        @endif
    </span>
</{{ $balise }}>
