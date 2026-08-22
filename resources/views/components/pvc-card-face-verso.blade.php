{{--
  VERSO de la carte — la face de la PLATEFORME.

  RIGOUREUSEMENT IDENTIQUE SUR TOUTES LES CARTES DE TOUS LES CLIENTS. Il ne
  prend aucun paramètre et ne lit aucune donnée de profil : le seul réglage
  qu'il reçoit est la variante, qui décide du fond et de l'encre.

  ═══════════════════════════════════════════════════════════════════════
  LA COMPOSITION EST ASYMÉTRIQUE, ET C'EST VOULU
  ═══════════════════════════════════════════════════════════════════════
  Deux zones, dans un rapport de deux tiers / un tiers :

      ┌───────────────────────────────┬─────────┐
      │ ▣ QrID                        │    ↑    │
      │ l'accroche, deux lignes       │  texte  │
      │                               │ pivoté  │
      │ ┌─────┐                       │   90°   │
      │ │ QR  │  scannez pour          │         │
      │ └─────┘  créer la vôtre        │         │
      └───────────────────────────────┴─────────┘

  Une composition centrée donne une carte d'organisme public. C'est le
  déséquilibre — masse à gauche, colonne étroite à droite — qui fait lire
  l'objet comme un objet dessiné.

  LE TEXTE DE LA COLONNE SE LIT DE BAS EN HAUT, comme sur une tranche de
  livre : c'est le sens de lecture qu'on adopte spontanément en inclinant la
  tête vers la droite, et le seul qui reste correct quand la carte est posée
  à plat sur une table.

  ═══════════════════════════════════════════════════════════════════════
  DENSITÉ
  ═══════════════════════════════════════════════════════════════════════
  Le QR occupe environ 45 % de la hauteur, le nom presque toute la largeur
  utile de la zone gauche, et aucune plage vide ne dépasse le quart de la
  hauteur. Une carte à moitié remplie se lit comme une maquette inachevée,
  pas comme un produit fini.

  LE QR MÈNE À LA PLATEFORME, PAS AU PORTEUR. Celui qui reçoit cette carte
  peut ainsi découvrir le produit et créer son propre compte : chaque carte
  distribuée devient un canal d'acquisition, sans rien coûter à son porteur.
  Le QR du RECTO, lui, reste celui du profil du client. Deux codes, deux
  destinations — ce n'est pas une erreur.

  LE CADRE BLANC EST CONSERVÉ, et volontairement. Le QR y est en orientation
  standard, sombre sur clair, conforme à ISO/IEC 18004 : c'est le code que
  scannera un inconnu, une fois, peut-être mal éclairé. On ne lui fait pas
  courir le risque d'un code inversé.
--}}
@props(['variante' => \App\Enums\VarianteCarte::DEFAUT])

@php
    $qrPlateforme = app(App\Services\QrCodeService::class)->plateformeSvg();
@endphp

<div class="pvc__face pvc__face--verso">
    <span class="pvc__visuel" aria-hidden="true"></span>
    <span class="pvc__reflet" aria-hidden="true"></span>

    {{-- ═══════════ ZONE ORGANIQUE — les deux tiers gauches ═══════════ --}}
    <span class="pvc__v-zone">

        {{-- HAUT : la signature.

             LE CARRÉ ET LE NOM SONT SUR LA MÊME LIGNE. Empilés — carré
             au-dessus, nom en dessous — ils formaient deux blocs distincts au
             lieu d'une signature, et le regard devait redescendre pour lire la
             marque. Côte à côte, ils se lisent d'un seul tenant.

             LE LOGO EST CELUI DE L'APPLICATION, pas un pictogramme propre à la
             carte : une marque qui se dessine différemment selon le support
             n'est plus une marque. --}}
        <span class="pvc__v-texte">
            <span class="pvc__v-marque">
                <x-brand :words="false" :link="false" class="pvc__v-logo"
                         :tone="$variante === \App\Enums\VarianteCarte::Verte ? 'light' : 'dark'" />

                <span class="pvc__v-nom">{{ config('app.name') }}</span>
            </span>

            <span class="pvc__v-accroche">{{ config('landing.brand.tagline') }}</span>
        </span>

        {{-- BAS : le code et son invitation, côte à côte.

             La mention est À DROITE du code et non en dessous : sous le code,
             elle repoussait le bloc vers le haut et rouvrait en bas la plage
             vide que cette composition cherche justement à supprimer. --}}
        <span class="pvc__v-qr">
            <span class="pvc__v-qr-cadre">{!! $qrPlateforme !!}</span>

            <span class="pvc__v-qr-texte">
                <span class="pvc__v-qr-mention">{{ config('landing.brand.card_cta') }}</span>
                <span class="pvc__v-nature">Protocole d'identité numérique</span>
            </span>
        </span>
    </span>

    {{-- ═══════════ COLONNE — le tiers droit ═══════════
         Elle remplace la bande horizontale du recto : sur cette face, une
         bande en bas viendrait s'ajouter à la colonne et fermerait la
         composition deux fois. --}}
    <span class="pvc__v-colonne">
        <span class="pvc__v-colonne-texte">{{ config('landing.brand.website') }}</span>
    </span>
</div>
