{{--
  CARTE PRÊTE POUR L'IMPRIMEUR — deux pages, recto puis verso.

  Page : 91,6 × 60 mm = 85,6 × 54 mm (ISO/IEC 7810 ID-1) + 3 mm de fonds
  perdus sur chaque bord. Le trait de coupe passe à 3 mm des bords ; le fond
  vert court jusqu'au bord de page, sinon une dérive de massicot d'un demi-
  millimètre laisse un liseré blanc.

  ZONE DE SÉCURITÉ : aucun texte à moins de 6 mm du bord (3 mm de fonds perdus
  + 3 mm de marge).

  CONTRAINTE DOMPDF : ni flexbox, ni grid, ni aspect-ratio. Tout est en
  positionnement absolu et en millimètres — le seul CSS que ce moteur rend
  fidèlement.

  QR : image PNG à 984 px sur 26 mm, soit ≈960 dpi, très au-delà des 300 dpi
  d'une impression offset. Le SVG serait plus élégant mais le support vectoriel
  de DomPDF est partiel, et une carte partie à l'impression avec un code
  approximatif ne se corrige pas par un déploiement.

  AUCUN COIN ARRONDI ICI, et c'est volontaire. Sur une carte physique, le
  rayon vient de la DÉCOUPE, jamais de l'impression : le fond doit courir
  jusqu'au bord de page, fonds perdus compris. Un arrondi dessiné dans le PDF
  laisserait quatre coins blancs à l'intérieur du trait de coupe — exactement
  le défaut que les fonds perdus servent à éviter.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Carte {{ $profile->slug }}</title>
    <style>
        @page { margin: 0; }

        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; }

        .page {
            position: relative;
            width: 91.6mm;
            height: 60mm;
            overflow: hidden;
            page-break-after: always;
        }

        .page:last-child { page-break-after: auto; }

        /* Le fond couvre TOUTE la page, fonds perdus compris. */
        .fond {
            position: absolute;
            top: 0; left: 0;
            width: 91.6mm;
            height: 60mm;
            background-color: {{ $profile->primary_color ?: '#0B3B2E' }};
        }

        /* Zone de sécurité : 6 mm depuis le bord de page. */
        .zone {
            position: absolute;
            top: 6mm; left: 6mm;
            width: 79.6mm;
            height: 48mm;
        }

        /* --- RECTO : nom, QR inversé, fonction --- */
        .nom {
            position: absolute;
            top: 0; left: 0;
            width: 79.6mm;
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            color: #FFFFFF;
            line-height: 1;
        }

        .qr {
            position: absolute;
            left: 26.8mm; top: 13mm;
            width: 26mm;
            height: 26mm;
        }

        .qr img { width: 26mm; height: 26mm; }

        .fonction {
            position: absolute;
            left: 0; bottom: 1mm;
            width: 79.6mm;
            text-align: center;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 2.2pt;
            color: #FFFFFF;
        }

        /* --- VERSO : la face de la PLATEFORME.
           Bande verticale à droite, texte tourné à 90°, comme à l'écran.

           ÉCART ASSUMÉ : le visuel organique de l'écran repose sur six
           dégradés RADIAUX, que DomPDF ne sait pas rendre. Le fond du verso
           imprimé est donc un aplat vert, avec un dégradé linéaire — le seul
           que ce moteur honore. Composition, bande et rotation, elles, sont
           reproduites à l'identique. */
        .verso-fond {
            position: absolute;
            top: 0; left: 0;
            width: 91.6mm;
            height: 60mm;
            background-image: linear-gradient(135deg,
                {{ $profile->primary_color ?: '#0B3B2E' }} 0%, #041A14 100%);
        }

        /* Bande verticale : un tiers de la largeur, sur le bord droit. */
        .bande {
            position: absolute;
            top: 0; right: 0;
            width: 30mm;
            height: 60mm;
            background-color: #041A14;
        }

        /* Le texte tourné. DomPDF honore transform: rotate() sur un bloc
           positionné, à condition de lui donner des dimensions explicites. */
        .rot {
            position: absolute;
            transform: rotate(-90deg);
            transform-origin: left top;
            white-space: nowrap;
            color: #FFFFFF;
        }

        .rot-nom {
            left: 21mm; top: 55mm;
            width: 50mm;
            font-size: 26pt;
            font-weight: bold;
        }

        .rot-accroche {
            left: 10mm; top: 55mm;
            width: 50mm;
            font-size: 7pt;
            font-weight: bold;
        }

        .rot-site {
            left: 5mm; top: 55mm;
            width: 50mm;
            font-size: 5.5pt;
            letter-spacing: 1pt;
        }
    </style>
</head>
<body>

{{-- ============================== RECTO ============================== --}}
<div class="page">
    <div class="fond"></div>

    <div class="zone">
        <div class="nom">{{ mb_strtoupper($profile->full_name) }}</div>

        <div class="qr"><img src="{{ $qrPng }}" alt=""></div>

        <div class="fonction">{{ mb_strtoupper($profile->job_title ?? '') }}</div>
    </div>
</div>

{{-- ============================== VERSO ==============================
     La face de la PLATEFORME. Aucune donnée du porteur : ce verso est
     rigoureusement identique sur toutes les cartes de tous les clients. --}}
<div class="page">
    <div class="verso-fond"></div>
    <div class="bande"></div>

    <div class="rot rot-nom">{{ mb_strtoupper(config('app.name')) }}</div>
    <div class="rot rot-accroche">{{ config('landing.brand.tagline') }}</div>
    <div class="rot rot-site">{{ mb_strtoupper(config('landing.brand.website')) }}</div>
</div>

</body>
</html>
