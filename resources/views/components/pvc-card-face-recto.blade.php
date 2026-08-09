{{--
  RECTO de la carte — le porteur. Nom, QR Code inversé, fonction.

  Extrait en partial pour que le design-system puisse montrer chaque face
  isolément, sans dupliquer une ligne de balisage.

  Attend : $profile. Calcule son QR lui-même.
--}}
@php
    $qrInverse = $profile->slug
        ? app(App\Services\QrCodeService::class)->invertedSvg($profile)
        : null;
@endphp

<div class="pvc__face pvc__face--recto">
    <span class="pvc__reflet" aria-hidden="true"></span>

    <span class="pvc__nom">{{ mb_strtoupper($profile->full_name) }}</span>

    <span class="pvc__qr">
        @if ($qrInverse)
            {!! $qrInverse !!}
        @else
            <span class="pvc__qr-absent">QR en préparation</span>
        @endif
    </span>

    <span class="pvc__fonction">{{ mb_strtoupper($profile->job_title ?? '') }}</span>
</div>
