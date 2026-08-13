{{--
  RECTO de la carte — le porteur. Nom, QR Code, fonction.

  Extrait en partial pour que le design-system puisse montrer chaque face
  isolément, sans dupliquer une ligne de balisage.

  LE QR SUIT LA VARIANTE : modules blancs sur la carte verte, modules vert
  profond sur la carte blanche. Sa zone de silence est transparente — c'est
  la carte elle-même qui la remplit, et donc elle qui assure le contraste.

  Attend : $profile. Calcule son QR lui-même.
--}}
@php
    $qrCarte = $profile->slug
        ? app(App\Services\QrCodeService::class)->carteSvg($profile)
        : null;
@endphp

<div class="pvc__face pvc__face--recto">
    <span class="pvc__reflet" aria-hidden="true"></span>

    <span class="pvc__nom">{{ mb_strtoupper($profile->full_name) }}</span>

    <span class="pvc__qr">
        @if ($qrCarte)
            {!! $qrCarte !!}
        @else
            <span class="pvc__qr-absent">QR en préparation</span>
        @endif
    </span>

    <span class="pvc__fonction">{{ mb_strtoupper($profile->job_title ?? '') }}</span>
</div>
