{{--
  RECTO de la carte — le porteur. Nom, QR Code, fonction.

  Extrait en partial pour que le design-system puisse montrer chaque face
  isolément, sans dupliquer une ligne de balisage.

  LE QR SUIT LA VARIANTE : modules blancs sur la carte verte, modules vert
  profond sur la carte blanche. Sa zone de silence est transparente — c'est
  la carte elle-même qui la remplit, et donc elle qui assure le contraste.

  Attend : $profile. Calcule son QR et la taille de son nom lui-même.
--}}
@php
    $qrCarte = $profile->slug
        ? app(App\Services\QrCodeService::class)->carteSvg($profile)
        : null;

    $nom = mb_strtoupper($profile->full_name);

    /*
     | LE NOM TIENT SUR UNE LIGNE, QUELLE QUE SOIT SA LONGUEUR.
     |
     | À taille fixe, « MOUHAMED DIONE » passait à la ligne — deux lignes
     | serrées au-dessus du QR, et toute la composition déséquilibrée.
     |
     | Le calcul vit dans App\Support\NomSurCarte, parce que le gabarit
     | d'impression en a besoin lui aussi, en millimètres. Deux calculs avec le
     | même coefficient divergeraient, et la divergence se verrait sur des
     | cartes déjà tirées.
     |
     | Ici l'unité est le cqw : largeur utile = 100 − 2 × 3,5cqw de marge.
     */
    $tailleNom = \App\Support\NomSurCarte::taille($nom, 93, 100);
    $surUneLigne = \App\Support\NomSurCarte::surUneLigne($tailleNom, 100);
@endphp

<div class="pvc__face pvc__face--recto">
    <span class="pvc__reflet" aria-hidden="true"></span>

    <span @class(['pvc__nom', 'pvc__nom--ligne' => $surUneLigne])
          style="font-size:{{ $tailleNom }}cqw">{{ $nom }}</span>

    <span class="pvc__qr">
        @if ($qrCarte)
            {!! $qrCarte !!}
        @else
            <span class="pvc__qr-absent">QR en préparation</span>
        @endif
    </span>

    <span class="pvc__fonction">{{ mb_strtoupper($profile->job_title ?? '') }}</span>
</div>
