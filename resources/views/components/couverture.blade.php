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

  Le nom est POSÉ EN FILIGRANE et non centré comme un titre : il doit se
  sentir, pas se lire. Un logo lisible au-dessus du portrait de quelqu'un
  ferait de sa carte une publicité pour nous.

  Props : profile, url (l'adresse de l'image, ou null)
--}}
@props(['profile', 'url' => null])

<header class="pubc__couverture">
    @if ($url)
        {{-- width et height sont déclarés : sans eux, la page saute au
             chargement de l'image et le médaillon se déplace sous le doigt. --}}
        <img src="{{ $url }}" alt=""
             class="pubc__couverture-image" width="840" height="300"
             decoding="async" fetchpriority="high">
    @else
        <span class="pubc__couverture-fond" aria-hidden="true">
            <span class="pubc__couverture-marque">{{ config('app.name') }}</span>
        </span>
    @endif

    {{-- ═══════════════ MÉDAILLON ═══════════════
         LA VÉRIFICATION PORTE SUR LES OCTETS, PAS SUR LE CHAMP.
         photo_path renseigné ne veut pas dire image disponible : sur un
         disque éphémère, la colonne survit au déploiement, le fichier non.
         Voir Profile::photoBinaire(). --}}
    <div class="pubc__medaillon">
        {{ $slot }}
    </div>
</header>
