{{--
  VERSO de la carte — la face de la PLATEFORME.

  RIGOUREUSEMENT IDENTIQUE SUR TOUTES LES CARTES DE TOUS LES CLIENTS. Il ne
  prend aucun paramètre et ne lit aucune donnée de profil : le seul réglage
  qu'il reçoit est la variante, qui décide du fond et de l'encre.

  COMPOSITION, reprise de la maquette validée :
    haut à gauche  — logo et nom de la marque ;
    sous le logo   — l'accroche, sur trois lignes ;
    droite         — le QR Code dans un cadre blanc fin, mention en dessous ;
    bas à gauche   — la mention de nature ;
    bas à droite   — l'adresse du site.

  LE QR MÈNE À LA PLATEFORME, PAS AU PORTEUR. Celui qui reçoit cette carte
  peut ainsi découvrir le produit et créer son propre compte : chaque carte
  distribuée devient un canal d'acquisition, sans rien coûter à son porteur.
  Le QR du RECTO, lui, reste celui du profil du client. Deux codes, deux
  destinations — ce n'est pas une erreur.

  LE CADRE BLANC EST CONSERVÉ, et volontairement. Le QR y est en orientation
  standard, sombre sur clair, conforme à ISO/IEC 18004 : c'est le code que
  scannera un inconnu, une fois, peut-être mal éclairé. On ne lui fait pas
  courir le risque d'un code inversé. Le cadre est réduit à une marge fine et
  régulière, juste la zone de silence nécessaire.
--}}
@props(['variante' => \App\Enums\VarianteCarte::DEFAUT])

@php
    $qrPlateforme = app(App\Services\QrCodeService::class)->plateformeSvg();
@endphp

<div class="pvc__face pvc__face--verso">
    <span class="pvc__visuel" aria-hidden="true"></span>
    <span class="pvc__reflet" aria-hidden="true"></span>

    {{-- ===================== COLONNE GAUCHE =====================
         LE LOGO EST CELUI DE L'APPLICATION, pas un pictogramme propre à la
         carte. Une marque qui se dessine différemment selon le support n'est
         plus une marque : c'est le composant x-brand qui sert ici, comme dans
         la navbar et le menu latéral.

         LE CARRÉ ET LE NOM SONT SUR LA MÊME LIGNE. Empilés — carré au-dessus,
         nom en dessous — ils formaient deux blocs distincts au lieu d'une
         signature, et le regard devait redescendre pour lire la marque.
         Côte à côte, ils se lisent d'un seul tenant, comme dans la navbar. --}}
    <span class="pvc__v-texte">
        <span class="pvc__v-marque">
            <x-brand :words="false" :link="false" class="pvc__v-logo"
                     :tone="$variante === \App\Enums\VarianteCarte::Verte ? 'light' : 'dark'" />

            <span class="pvc__v-nom">{{ config('app.name') }}</span>
        </span>

        <span class="pvc__v-accroche">{{ config('landing.brand.tagline') }}</span>
    </span>

    {{-- ===================== QR DE LA PLATEFORME ===================== --}}
    <span class="pvc__v-qr">
        <span class="pvc__v-qr-cadre">{!! $qrPlateforme !!}</span>
        <span class="pvc__v-qr-mention">{{ config('landing.brand.card_cta') }}</span>
    </span>

    {{-- ===================== PIED ===================== --}}
    <span class="pvc__v-pied">
        <span class="pvc__v-nature">Protocole d'identité numérique</span>
        <span class="pvc__v-site">{{ config('landing.brand.website') }}</span>
    </span>

</div>
