{{--
  VERSO de la carte — la face de la PLATEFORME.

  COMPOSITION, en contrepoint du recto :
    zone visuelle (deux tiers gauche) — logo en haut, fond organique,
      code-barres en bas avec le slug dessous ;
    bande verticale (tiers droit)     — nom de la marque, accroche, site,
      tournés à 90° et lus de bas en haut.

  UNE SEULE DONNÉE DE PROFIL y figure : le code-barres et son slug, qui
  encodent le lien public. Tout le reste est identique sur les cartes de tous
  les clients.

  Le profil est donc facultatif : sans lui, le verso s'affiche sans son
  code-barres, et rien ne casse.
--}}
@props(['profile' => null])

@php
    $barres = $profile?->slug
        ? app(App\Services\QrCodeService::class)->barcodeSvg($profile)
        : null;
@endphp

<div class="pvc__face pvc__face--verso">
    <span class="pvc__visuel" aria-hidden="true"></span>
    <span class="pvc__reflet" aria-hidden="true"></span>

    {{-- ================= ZONE VISUELLE, deux tiers gauche ================= --}}
    <span class="pvc__zone">
        <x-brand-mark class="pvc__logo" />

        @if ($barres)
            <span class="pvc__barres">
                {!! $barres !!}
                <span class="pvc__barres-slug">{{ $profile->slug }}</span>
            </span>
        @endif
    </span>

    {{-- ============ BANDE VERTICALE, tiers droit ============
         row-reverse : le premier enfant — le nom — se place contre le bord. --}}
    <span class="pvc__bande">
        <span class="pvc__bande-nom">{{ mb_strtoupper(config('app.name')) }}</span>
        <span class="pvc__bande-accroche">{{ config('landing.brand.tagline') }}</span>
        <span class="pvc__bande-site">{{ config('landing.brand.website') }}</span>
    </span>
</div>
