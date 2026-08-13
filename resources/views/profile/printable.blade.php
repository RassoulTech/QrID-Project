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

        /* ============================ RECTO ============================
           DENSITÉ — les valeurs suivent celles de l'écran, converties dans le
           repère du papier. Une carte dense à l'écran et aérée à l'impression
           serait le pire des deux : l'aperçu ne dirait plus la vérité sur ce
           que le client va tenir.

           La zone utile fait 79,6 × 48 mm. Le nom, le QR et la fonction s'y
           répartissent sur toute la hauteur, sans zone morte. */
        .nom {
            position: absolute;
            top: 0; left: 0;
            width: 79.6mm;
            text-align: center;
            /* 26pt ≈ 12 % de la largeur de la carte, comme les 12cqw de
               l'écran. Le resserrement gagne la place du passage à la taille
               supérieure. */
            font-size: 26pt;
            font-weight: bold;
            letter-spacing: -0.8pt;
            color: {{ $variante->encre() }};
            line-height: 0.94;
        }

        /* QR à 27,5 mm sur 54 mm de carte utile, soit ≈47 % de la hauteur —
           la même proportion qu'à l'écran. */
        .qr {
            position: absolute;
            left: 26.05mm; top: 11.5mm;
            width: 27.5mm;
            height: 27.5mm;
        }

        .qr img { width: 27.5mm; height: 27.5mm; }

        .fonction {
            position: absolute;
            left: 0; bottom: 0.5mm;
            width: 79.6mm;
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
            left: 6mm; top: 5.5mm;
            width: 10mm;
            height: 10mm;
            background-color: {{ $variante === \App\Enums\VarianteCarte::Verte ? '#1E9E7A' : '#0B3B2E' }};
            border-radius: 2.4mm;
            text-align: center;
            /* DomPDF ne centre pas verticalement : la hauteur de ligne le fait
               à sa place, réglée sur la hauteur du carré. */
            line-height: 10mm;
            font-size: 12pt;
            font-weight: bold;
            color: #FFFFFF;
            letter-spacing: 0.3pt;
        }

        .v-nom {
            position: absolute;
            left: 6mm; top: 18mm;
            font-size: 24pt;
            font-weight: bold;
            letter-spacing: -0.8pt;
            color: {{ $variante->encre() }};
        }

        .v-accroche {
            position: absolute;
            left: 6mm; top: 29mm;
            width: 40mm;
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
            right: 6mm; top: 5.5mm;
            width: 26mm;
            background-color: #FFFFFF;
            padding: 1.2mm;
        }

        .v-qr img { width: 26mm; height: 26mm; }

        .v-qr-mention {
            position: absolute;
            right: 6mm; top: 35.5mm;
            width: 28.4mm;
            text-align: center;
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
            color: {{ $variante->encre() }};
        }

        /* --- Pied : nature à gauche, adresse à droite --- */
        .v-nature {
            position: absolute;
            left: 6mm; bottom: 4.5mm;
            font-size: 6pt;
            letter-spacing: 0.9pt;
            color: {{ $variante->encre() }};
        }

        .v-site {
            position: absolute;
            right: 6mm; bottom: 4.5mm;
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

    <div class="v-logo">{{ \App\Support\Marque::monogramme() }}</div>
    <div class="v-nom">{{ config('app.name') }}</div>
    <div class="v-accroche">{{ config('landing.brand.tagline') }}</div>

    <div class="v-qr"><img src="{{ $qrVerso }}" alt=""></div>
    <div class="v-qr-mention">{{ mb_strtoupper(config('landing.brand.card_cta')) }}</div>

    <div class="v-nature">PROTOCOLE D'IDENTITÉ NUMÉRIQUE</div>
    <div class="v-site">{{ mb_strtoupper(config('landing.brand.website')) }}</div>
</div>

</body>
</html>
