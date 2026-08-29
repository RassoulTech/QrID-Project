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

  Reçoit : $profile, $variante, $nom, $marge, $utile, $tailleNom, $surUneLigne,
  $qrRecto, $qrVerso, $fondVerso.

  LA TAILLE DU NOM EST CALCULÉE, non écrite ici : App\Support\NomSurCarte la
  déduit de la longueur, avec le même coefficient qu'à l'écran. Une valeur
  fixe faisait passer « MOUHAMED DIONE » à la ligne.
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

        /* MARGE RÉGULIÈRE SUR LES QUATRE CÔTÉS — 6,4 mm depuis le bord de
           page, soit 3,4 mm depuis le trait de coupe. Au-delà des 3 mm de zone
           de sécurité exigés par l'impression, et exactement les 4 % de largeur
           employés à l'écran. Trois valeurs différentes donnaient une bordure
           irrégulière que l'œil perçoit sans savoir la nommer. */
        .zone {
            position: absolute;
            top: {{ $marge }}mm; left: {{ $marge }}mm;
            width: {{ $utile }}mm;
            height: {{ 60 - 2 * $marge }}mm;
        }

        /* ============================ RECTO ============================
           DENSITÉ — les valeurs suivent celles de l'écran, converties dans le
           repère du papier. Une carte dense à l'écran et aérée à l'impression
           serait le pire des deux : l'aperçu ne dirait plus la vérité sur ce
           que le client va tenir.

           Le nom, le QR et la fonction se répartissent sur toute la hauteur
           utile, sans zone morte. */
        .nom {
            position: absolute;
            top: 0; left: 0;
            width: {{ $utile }}mm;
            text-align: center;
            /* Taille CALCULÉE d'après la longueur du nom, par le même code
               qu'à l'écran — voir App\Support\NomSurCarte. */
            font-size: {{ $tailleNom }}pt;
            font-weight: bold;
            letter-spacing: -0.7pt;
            color: {{ $variante->encre() }};
            line-height: 0.94;
            @if ($surUneLigne) white-space: nowrap; @endif
        }

        /* QR à 27,4 mm sur 54 mm de carte, soit ≈50 % de la hauteur — la même
           proportion que les 32cqw de l'écran. */
        .qr {
            position: absolute;
            left: {{ round(($utile - 27.4) / 2, 2) }}mm; top: 10.6mm;
            width: 27.4mm;
            height: 27.4mm;
        }

        .qr img { width: 27.4mm; height: 27.4mm; }

        .fonction {
            position: absolute;
            left: 0; bottom: 0;
            width: {{ $utile }}mm;
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 2pt;
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

        /* --- Colonne gauche : logo, nom de la marque, accroche ---

           LE LOGO EST CELUI DE L'APPLICATION — le carré du monogramme, dessiné
           ici en CSS plutôt qu'en image. DomPDF rend fidèlement un bloc à fond
           plein avec du texte centré, et cette voie évite d'embarquer un
           fichier de plus dans un document déjà lourd de trois images.

           Le carré est AU-DESSUS du nom, jamais à côté : le nom est écrit en
           grand juste en dessous. */
        .v-logo {
            position: absolute;
            left: {{ $marge }}mm; top: {{ $marge }}mm;
            width: 11mm;
            height: 11mm;
            background-color: {{ $variante === \App\Enums\VarianteCarte::Verte ? '#1E9E7A' : '#0B3B2E' }};
            border-radius: 2.6mm;
            text-align: center;
            /* DomPDF ne centre pas verticalement : la hauteur de ligne le fait
               à sa place, réglée sur la hauteur du carré. */
            line-height: 11mm;
            font-size: 13pt;
            font-weight: bold;
            color: #FFFFFF;
            letter-spacing: 0.3pt;
        }

        /* LE NOM EST SUR LA MÊME LIGNE QUE LE CARRÉ, à sa droite. Empilés, les
           deux formaient deux blocs distincts au lieu d'une signature, et le
           regard devait redescendre pour lire la marque. */
        .v-nom {
            position: absolute;
            left: {{ $marge + 13.4 }}mm; top: {{ $marge }}mm;
            /* Même hauteur de ligne que le carré : les deux s'alignent sur
               leur milieu sans calcul de position supplémentaire. */
            line-height: 11mm;
            font-size: 25pt;
            font-weight: bold;
            letter-spacing: -0.8pt;
            color: {{ $variante->encre() }};
        }

        .v-accroche {
            position: absolute;
            left: {{ $marge }}mm; top: {{ $marge + 14 }}mm;
            width: 41mm;
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.42;
            color: {{ $variante->encre() }};
        }

        /* --- QR de la plateforme, à droite ---
           ALIGNÉ SUR LE HAUT du bloc de texte, et non centré : deux blocs qui
           partent de la même ligne se lisent comme une composition.

           Cadre blanc réduit à une marge fine et régulière : c'est la zone de
           silence du code, pas une décoration. Blanc en dur, jamais dérivé de
           la variante — ce code doit rester sombre sur clair. */
        .v-qr {
            position: absolute;
            right: {{ $marge }}mm; top: {{ $marge }}mm;
            width: 26mm;
            background-color: #FFFFFF;
            padding: 1.2mm;
        }

        .v-qr img { width: 26mm; height: 26mm; }

        .v-qr-mention {
            position: absolute;
            right: {{ $marge }}mm; top: {{ $marge + 30 }}mm;
            width: 28.4mm;
            text-align: center;
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
            color: {{ $variante->encre() }};
        }

        /* --- Pied : nature à gauche, adresse à droite ---
           Même marge que partout ailleurs : c'est la régularité de la bordure
           qui fait qu'une carte paraît soignée. */
        .v-nature {
            position: absolute;
            left: {{ $marge }}mm; bottom: {{ $marge }}mm;
            font-size: 6pt;
            letter-spacing: 0.9pt;
            color: {{ $variante->encre() }};
        }

        .v-site {
            position: absolute;
            right: {{ $marge }}mm; bottom: {{ $marge }}mm;
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: 0.9pt;
            color: {{ $variante->encre() }};
        }
    </style>
</head>
<body>

{{-- ============================== RECTO ============================== --}}
<div class="page">
    <div class="fond"></div>

    <div class="zone">
        <div class="nom">{{ $nom }}</div>

        <div class="qr"><img src="{{ $qrRecto }}" alt=""></div>

        <div class="fonction">{{ mb_strtoupper($profile->job_title ?? '') }}</div>
    </div>
</div>

{{-- ============================== VERSO ==============================
     La face de la PLATEFORME. Aucune donnée du porteur : ce verso est
     rigoureusement identique sur toutes les cartes de tous les clients. --}}
<div class="page">
    <div class="verso-fond"><img src="{{ $fondVerso }}" alt=""></div>

    <div class="v-logo">{{ \App\Support\Marque::monogramme() }}</div>
    <div class="v-nom">{{ config('app.name') }}</div>
    <div class="v-accroche">{{ config('landing.brand.tagline') }}</div>

    <div class="v-qr"><img src="{{ $qrVerso }}" alt=""></div>
    <div class="v-qr-mention">{{ mb_strtoupper(config('landing.brand.card_cta')) }}</div>

    <div class="v-nature">{{ mb_strtoupper(__('card.protocole')) }}</div>
    <div class="v-site">{{ mb_strtoupper(config('landing.brand.website')) }}</div>
</div>

</body>
</html>
