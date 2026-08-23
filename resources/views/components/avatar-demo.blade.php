{{--
  x-avatar-demo — le portrait de la maquette, dessiné.

      <x-avatar-demo />

  ═══════════════════════════════════════════════════════════════════════
  POURQUOI UN DESSIN, ET NON UNE PHOTOGRAPHIE
  ═══════════════════════════════════════════════════════════════════════
  La landing montrait la photo du profil de démonstration — c'est-à-dire le
  visage du propriétaire du compte. Une page d'accueil qui présente le
  produit à des inconnus ne doit pas présenter une personne réelle : le
  visiteur croit voir un client, et le propriétaire se retrouve affiché sur
  la vitrine de son propre produit.

  Une photographie de banque d'images poserait les deux mêmes problèmes en
  pire : elle représente quelqu'un de réel, elle exige une licence, et elle
  coûte une requête réseau sur une 3G.

  Un portrait DESSINÉ ne représente personne, ne se télécharge pas, et se
  lit immédiatement comme une illustration — ce qu'une maquette doit être.

  Les couleurs viennent des jetons de la marque : l'avatar s'accorde à la
  carte qui le porte, dans les deux thèmes, sans seconde version.
--}}
@props(['taille' => 72])

<svg viewBox="0 0 100 100"
     width="{{ $taille }}" height="{{ $taille }}"
     {{ $attributes->merge(['class' => 'avatar-demo']) }}
     role="img" aria-label="{{ __('Portrait d\'illustration') }}">

    {{-- Le fond : un dégradé de marque, comme le médaillon sans photo de la
         page publique. La cohérence des deux surfaces n'est pas un détail —
         c'est ce qui fait reconnaître le produit d'une page à l'autre. --}}
    <defs>
        <linearGradient id="avatar-fond" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#1E9E7A"/>
            <stop offset="100%" stop-color="#0B3B2E"/>
        </linearGradient>

        {{-- LE CADRE EST CARRÉ, comme le médaillon de la page publique.
             Il l'était rond : le portrait flottait alors dans un carré aux
             coins vides, et le médaillon paraissait mal découpé. Les épaules
             débordent volontairement du bas — c'est ce débordement qui donne
             l'impression d'un buste cadré, et non d'une vignette. --}}
        <clipPath id="avatar-cadre">
            <rect width="100" height="100"/>
        </clipPath>
    </defs>

    <g clip-path="url(#avatar-cadre)">
        <rect width="100" height="100" fill="url(#avatar-fond)"/>

        {{-- UN PORTRAIT, ET NON UN PICTOGRAMME.
             Une tête claire sur un fond vert se lit comme l'icône « compte »
             d'une interface — exactement ce qu'un médaillon ne doit pas être.
             Un teint, une chevelure et une chemise distincts suffisent à
             faire basculer la lecture du côté de l'illustration. --}}

        {{-- Le buste : la chemise. --}}
        <path d="M50 66c-22 0-39 14-43 34h86c-4-20-21-34-43-34z" fill="#F4F6F5"/>

        {{-- Le col, ouvert : sans lui, le buste est une masse, pas un
             vêtement. --}}
        <path d="M41 68l9 11 9-11-4-2-5 6-5-6z" fill="#0B3B2E" opacity=".22"/>

        {{-- Le cou, sous la tête : il rattache le visage aux épaules. --}}
        <path d="M42 52h16v14H42z" fill="#C69A72"/>

        {{-- La tête. Aucun trait de visage : des yeux et une bouche feraient
             un personnage, et un personnage finit par ressembler à
             quelqu'un. Une silhouette reste une silhouette. --}}
        <circle cx="50" cy="41" r="21" fill="#E0B48C"/>

        {{-- La chevelure, posée en calotte, débordant légèrement sur les
             tempes. --}}
        <path d="M29 42a21 21 0 0 1 42 0c1-7-2-12-7-15-4-3-9-4-14-4s-10 1-14 4c-5 3-8 8-7 15z"
              fill="#2B1B12"/>
        <path d="M27 44c-1-4 0-7 2-8 1 4 1 7 0 9zM71 44c1-4 0-7-2-8-1 4-1 7 0 9z"
              fill="#2B1B12"/>
    </g>
</svg>
