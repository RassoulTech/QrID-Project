{{--
  CARTE PRÊTE POUR L'IMPRIMEUR — deux pages, recto puis verso.

  Page : 91,6 × 60 mm = 85,6 × 54 mm (ISO/IEC 7810 ID-1) + 3 mm de fonds
  perdus sur chaque bord. Le trait de coupe passe à 3 mm des bords ; le fond
  court jusqu'au bord de page, sinon une dérive de massicot d'un demi-
  millimètre laisse un liseré blanc.

  ZONE DE SÉCURITÉ : aucun texte à moins de 6 mm du bord (3 mm de fonds perdus
  + 3 mm de marge).

  CONTRAINTE DOMPDF : ni flexbox, ni grid, ni aspect-ratio, ni dégradé radial.
  Tout est en positionnement absolu et en millimètres — le seul CSS que ce
  moteur rend fidèlement. Le fond organique du verso est donc une IMAGE,
  produite par CardTextureService : voir l'en-tête de ce fichier-là.

  QR : images PNG haute définition. Le SVG serait plus élégant mais le support
  vectoriel de DomPDF est partiel, et une carte partie à l'impression avec un
  code approximatif ne se corrige pas par un déploiement.

  AUCUN COIN ARRONDI, et c'est volontaire. Sur une carte physique, le rayon
  vient de la DÉCOUPE, jamais de l'impression : le fond doit courir jusqu'au
  bord de page, fonds perdus compris. Un arrondi dessiné dans le PDF laisserait
  quatre coins blancs à l'intérieur du trait de coupe — exactement le défaut
  que les fonds perdus servent à éviter.

  DEUX QR CODES, DEUX DESTINATIONS :
    recto — la carte du porteur, aux couleurs de la variante ;
    verso — la PLATEFORME, en orientation standard dans un cadre blanc.
  Ce n'est pas une erreur : la seconde permet à qui reçoit la carte de
  découvrir le produit.

  Reçoit : $profile, $variante, $qrRecto, $qrVerso, $fondVerso.
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
            background-color: {{ $variante->fond() }};
        }

        /* Zone de sécurité : 6 mm depuis le bord de page. */
        .zone {
            position: absolute;
            top: 6mm; left: 6mm;
            width: 79.6mm;
            height: 48mm;
        }

        /* ============================ RECTO ============================ */
        .nom {
            position: absolute;
            top: 0; left: 0;
            width: 79.6mm;
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            color: {{ $variante->encre() }};
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
            color: {{ $variante->encre() }};
        }

        /* ============================ VERSO ============================
           Le fond organique est une image posée à plat sur toute la page,
           fonds perdus compris. Elle contient DÉJÀ la couleur de la variante :
           aucune superposition, donc aucun risque d'écart de teinte entre le
           bord de l'image et le fond de la page. */
        .verso-fond {
            position: absolute;
            top: 0; left: 0;
            width: 91.6mm;
            height: 60mm;
        }

        .verso-fond img { width: 91.6mm; height: 60mm; }

        /* --- Colonne gauche : marque et accroche --- */
        .v-nom {
            position: absolute;
            left: 6mm; top: 6mm;
            font-size: 17pt;
            font-weight: bold;
            letter-spacing: -0.5pt;
            color: {{ $variante->encre() }};
        }

        .v-accroche {
            position: absolute;
            left: 6mm; top: 15mm;
            width: 38mm;
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.45;
            color: {{ $variante->encre() }};
        }

        /* --- QR de la plateforme, à droite ---
           Cadre blanc réduit à une marge fine et régulière : c'est la zone de
           silence du code, pas une décoration. Blanc en dur, jamais dérivé de
           la variante — ce code doit rester sombre sur clair. */
        .v-qr {
            position: absolute;
            right: 6mm; top: 17mm;
            width: 24mm;
            background-color: #FFFFFF;
            padding: 1.2mm;
        }

        .v-qr img { width: 24mm; height: 24mm; }

        .v-qr-mention {
            position: absolute;
            right: 6mm; top: 44mm;
            width: 26.4mm;
            text-align: center;
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
            color: {{ $variante->encre() }};
        }

        /* --- Pied : nature à gauche, adresse à droite --- */
        .v-nature {
            position: absolute;
            left: 6mm; bottom: 5.5mm;
            font-size: 5.5pt;
            letter-spacing: 1pt;
            color: {{ $variante->encre() }};
        }

        .v-site {
            position: absolute;
            right: 6mm; bottom: 5.5mm;
            font-size: 5.5pt;
            font-weight: bold;
            letter-spacing: 1pt;
            color: {{ $variante->encre() }};
        }
    </style>
</head>
<body>

{{-- ============================== RECTO ============================== --}}
<div class="page">
    <div class="fond"></div>

    <div class="zone">
        <div class="nom">{{ mb_strtoupper($profile->full_name) }}</div>

        <div class="qr"><img src="{{ $qrRecto }}" alt=""></div>

        <div class="fonction">{{ mb_strtoupper($profile->job_title ?? '') }}</div>
    </div>
</div>

{{-- ============================== VERSO ==============================
     La face de la PLATEFORME. Aucune donnée du porteur : ce verso est
     rigoureusement identique sur toutes les cartes de tous les clients. --}}
<div class="page">
    <div class="verso-fond"><img src="{{ $fondVerso }}" alt=""></div>

    <div class="v-nom">{{ config('app.name') }}</div>
    <div class="v-accroche">{{ config('landing.brand.tagline') }}</div>

    <div class="v-qr"><img src="{{ $qrVerso }}" alt=""></div>
    <div class="v-qr-mention">{{ mb_strtoupper(config('landing.brand.card_cta')) }}</div>

    <div class="v-nature">PROTOCOLE D'IDENTITÉ NUMÉRIQUE</div>
    <div class="v-site">{{ mb_strtoupper(config('landing.brand.website')) }}</div>
</div>

</body>
</html>
