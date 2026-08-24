{{--
  x-couverture — la bannière du haut de la carte publique.

      <x-couverture :profile="$profile" :url="$couvertureUrl" />

  ═══════════════════════════════════════════════════════════════════════
  DEUX CAS, ET AUCUN VIDE
  ═══════════════════════════════════════════════════════════════════════
  Le porteur a choisi une image  →  elle occupe toute la bande.
  Il n'a rien choisi             →  un décor composé, aux couleurs de la
                                    marque, qui porte son nom.

  IL N'Y A PAS DE TROISIÈME CAS. Une bande vide au-dessus d'un profil se lit
  comme une image qui n'a pas fini de charger, et c'est la première chose
  qu'on voit après un scan : le moment exact où un inconnu décide s'il
  continue.

  ═══════════════════════════════════════════════════════════════════════
  LE DÉCOR PAR DÉFAUT
  ═══════════════════════════════════════════════════════════════════════
  Quatre couches, toutes en CSS, aucune image à télécharger sur une 3G :

    1. le dégradé de marque, en base ;
    2. deux halos radiaux très diffus, qui cassent la platitude d'un aplat
       sans dessiner de forme reconnaissable ;
    3. une trame de modules carrés — l'écho du QR Code — à 3,5 % ;
    4. le nom du produit, en grand, très transparent, débordant du cadre.

  Le nom du produit est POSÉ EN FILIGRANE et non centré comme un titre : il
  doit se sentir, pas se lire. Un logo lisible au-dessus de l'identité de
  quelqu'un ferait de sa carte une publicité pour nous.

  ═══════════════════════════════════════════════════════════════════════
  ELLE PORTE L'IDENTITÉ
  ═══════════════════════════════════════════════════════════════════════
  Le nom, la fonction et l'entreprise sont DANS la couverture, en bas à
  gauche, sur un voile sombre. Ils ne sont plus répétés en dessous : deux
  fois la même information à quelques pixels d'écart repousserait les
  coordonnées d'un écran entier.

  Props : profile, url (l'adresse de l'image, ou null)
--}}
@props(['profile', 'url' => null])

<header class="pubc__couverture">
    @if ($url)
        {{-- width et height sont déclarés : sans eux, la page saute au
             chargement de l'image et le nom se déplace sous le doigt. --}}
        <img src="{{ $url }}" alt=""
             class="pubc__couverture-image" width="960" height="680"
             decoding="async" fetchpriority="high">
    @else
        <span class="pubc__couverture-fond" aria-hidden="true">
            <span class="pubc__couverture-marque">
                <span class="pubc__couverture-puce">
                    <span></span><span></span><span></span><span></span>
                </span>
                {{ config('app.name') }}
            </span>
        </span>
    @endif

    {{-- ═══════════════ LE VOILE ═══════════════
         IL N'EST PAS DÉCORATIF, IL EST LA CONDITION DE LISIBILITÉ.

         Le nom est posé sur une image que le porteur choisit : elle peut
         être un ciel blanc, un mur clair, une photo surexposée. Sans voile,
         du blanc sur blanc — et rien à l'écran n'aurait prévenu.

         Le dégradé est calibré pour que le PIRE CAS tienne : sur une image
         entièrement blanche, l'encre du texte reste au-dessus de 4,5:1.
         Les mesures sont dans la note de _carte-publique.scss. --}}
    <span class="pubc__couverture-voile" aria-hidden="true"></span>

    {{-- ═══════════════ L'IDENTITÉ, POSÉE SUR L'IMAGE ═══════════════
         En bas à gauche : c'est le point de départ de la lecture, et c'est
         là que le voile est le plus dense. --}}
    <div class="pubc__identite">
        <h1 class="pubc__nom">{{ $profile->full_name }}</h1>

        @if ($profile->job_title)
            <p class="pubc__role">{{ $profile->job_title }}</p>
        @endif

        @if ($profile->company)
            <p class="pubc__entreprise">{{ $profile->company }}</p>
        @endif
    </div>
</header>
