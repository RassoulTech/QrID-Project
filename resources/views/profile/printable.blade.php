{{--
  CARTE PRÊTE POUR L'IMPRIMEUR — deux pages, recto puis verso.
  Direction A « Teranga institutionnel » : recto au nom du porteur, verso
  baobab au claustra, lisière tissée sur la tranche basse des deux faces.

  Page : 91,6 × 60 mm = 85,6 × 54 mm (ISO/IEC 7810 ID-1) + 3 mm de fonds
  perdus sur chaque bord. Le trait de coupe passe à 3 mm des bords ; le fond
  court jusqu'au bord de page, sinon une dérive de massicot d'un demi-
  millimètre laisse un liseré blanc.

  ZONE DE SÉCURITÉ : aucun texte à moins de $marge (6 mm) du bord de page,
  soit 3 mm du trait de coupe. La composition prend 5 mm depuis le trait —
  les proportions de la maquette (1,5 em de carte) — au-dessus de ce plancher.

  CONTRAINTE DOMPDF : ni flexbox, ni grid, ni aspect-ratio, ni motif SVG
  répété, ni dégradé radial. Tout est en positionnement absolu et en
  millimètres, plus un tableau pour la ligne du haut — le seul CSS que ce
  moteur rend fidèlement (vérifié en rendu réel, page rastérisée). Le
  claustra et la lisière sont donc DÉJÀ PEINTS dans l'image de fond, produite
  par CardTextureService : voir l'en-tête de ce fichier-là.

  POLICE : DejaVu Sans, embarquée par DomPDF. Schibsted Grotesk n'existe dans
  le projet qu'en woff2 variable, sous-ensemble latin : DomPDF ne lit pas ce
  format, et le sous-ensemble ne couvre pas tous les noms (le ŋ wolof, par
  exemple). DejaVu les couvre tous ; une carte imprimée avec un nom troué ne
  se corrige pas.

  QR CODES — TOUJOURS SOMBRES SUR UN PANNEAU CLAIR (ISO/IEC 18004), sur les
  deux faces et dans les deux variantes. Les PNG portent leur zone de silence
  de 4 modules, sur fond blanc ; le panneau blanc les prolonge. Un code
  inversé (clair sur vert) est lu par les lecteurs récents, ignoré par
  d'autres, et leur échec est silencieux : sur un objet qu'on ne peut plus
  corriger, ce risque ne se prend pas.

  AUCUN COIN ARRONDI À LA PAGE, et c'est volontaire. Sur une carte physique,
  le rayon vient de la DÉCOUPE, jamais de l'impression : le fond doit courir
  jusqu'au bord de page, fonds perdus compris. Seuls les PANNEAUX intérieurs
  ont un rayon.

  DEUX QR CODES, DEUX DESTINATIONS :
    recto — la carte du porteur ;
    verso — la PLATEFORME, en orientation standard, dans son panneau.
  Ce n'est pas une erreur : le second permet à qui reçoit la carte de
  découvrir le produit.

  Reçoit : $profile, $variante, $marge, $qrRecto, $qrVerso, $fondRecto,
  $fondVerso.
--}}
@php
    $fp = 3.0;                       // fonds perdus
    $bord = max($marge, $fp + 5.0);  // composition : 5 mm du trait de coupe, jamais sous le plancher
    $lisiere = 1.4;                  // hauteur visible de la lisière
    $basUtile = $fp + 54 - $lisiere; // bas de la zone utile, au-dessus de la lisière
    $largeurUtile = 85.6 - 2 * 5.0;  // 75,6 mm

    $verte = $variante === \App\Enums\VarianteCarte::Verte;

    // Les encres du recto, selon la variante — palette de la direction A.
    $encre = [
        'nom' => $verte ? '#FFFCF6' : '#0D3B2E',
        'fonction' => $verte ? '#6FD1A8' : '#146A50',
        'entreprise' => $verte ? '#EE9B72' : '#A9481F',
        'panneau' => $verte ? '#FFFFFF' : '#E0D5C1',
    ];

    // QR du recto : 25 mm de code (≥ 22 mm exigés), panneau de 26,8 mm calé
    // dans le coin bas-droit. Le claustra de la face ivoire occupe le coin
    // opposé (voir CardTextureService::claustraEnCoin).
    $qrR = 25.0;
    $panR = $qrR + 1.8;
    $panRx = $fp + 85.6 - 5.0 - $panR;
    $panRy = $basUtile - 4.2 - $panR;

    // QR du verso : 31 mm de code, panneau de 33 mm, à gauche, à 5 mm du
    // trait de coupe en haut comme à gauche — une marge régulière.
    $qrV = 31.0;
    $panV = $qrV + 2.0;
    $panVy = $bord;
    $colV = $bord + $panV + 4.5;     // colonne de droite du verso
    $largeurColV = $fp + 85.6 - 5.0 - $colV;

    /*
     | LE MONOGRAMME de la direction A : le carré de repérage du QR devenu un
     | Q à queue dorée. En SVG dans une balise <img> — DomPDF le rend net
     | (php-svg-lib : rectangles arrondis, trait, bout rond), ce que la même
     | forme bâtie en blocs CSS ne garantissait pas.
     */
    $monogramme = 'data:image/svg+xml;base64,'.base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">'
        .'<rect x="3.5" y="3.5" width="29" height="29" rx="8" fill="none" stroke="#FFFCF6" stroke-width="5"/>'
        .'<rect x="12.5" y="12.5" width="11" height="11" rx="2.5" fill="#FFFCF6"/>'
        .'<path d="M27.5 27.5 36 36" fill="none" stroke="#E2B85A" stroke-width="5.5" stroke-linecap="round"/>'
        .'</svg>'
    );

    $entreprise = trim((string) $profile->company);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }} — {{ $profile->full_name }}</title>
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

        /* Le fond couvre TOUTE la page, fonds perdus compris. Il porte déjà
           la couleur de la face, le claustra et la lisière : aucune
           superposition, donc aucun écart de teinte au bord de l'image. */
        .fond { position: absolute; top: 0; left: 0; width: 91.6mm; height: 60mm; }

        .fond img { width: 91.6mm; height: 60mm; }

        /* LE PANNEAU DU QR : blanc, comme la zone de silence cuite dans le
           PNG — aucune marche de teinte autour du code. Rayon de 1,8 mm
           (0,55 em de carte), comme la maquette. */
        .panneau {
            position: absolute;
            background-color: #FFFFFF;
            border-radius: 1.8mm;
        }

        /* ============================ RECTO ============================
           Ligne du haut : le nom et la fonction à gauche, l'entreprise à
           droite en capitales latérite — la composition de la maquette.
           Un TABLEAU et non deux blocs absolus : la largeur du nom dépend
           de celle de l'entreprise, et seul le tableau sait la partager. */
        .haut {
            position: absolute;
            top: {{ $fp + 4.6 }}mm; left: {{ $bord }}mm;
            width: {{ $largeurUtile }}mm;
            border-collapse: collapse;
        }

        .haut td { padding: 0; vertical-align: top; }

        .nom {
            /* 1,42 em de carte, graisse 800 dans la maquette — gras ici. */
            font-size: 13.2pt;
            font-weight: bold;
            letter-spacing: -0.3pt;
            line-height: 1.08;
            color: {{ $encre['nom'] }};
        }

        .fonction {
            margin-top: 1.4mm;
            font-size: 7pt;
            font-weight: bold;
            line-height: 1.25;
            color: {{ $encre['fonction'] }};
        }

        .entreprise {
            padding-left: 3.3mm;
            padding-top: 0.6mm;
            text-align: right;
            font-size: 6.2pt;
            font-weight: bold;
            letter-spacing: 0.9pt;
            line-height: 1.3;
            color: {{ $encre['entreprise'] }};
        }

        .entreprise-courte { white-space: nowrap; }

        .entreprise-longue { width: 30mm; }

        .panneau-recto {
            left: {{ $panRx }}mm; top: {{ $panRy }}mm;
            width: {{ $panR }}mm; height: {{ $panR }}mm;
            @unless ($verte) border: 0.25mm solid {{ $encre['panneau'] }}; @endunless
        }

        .qr-recto { position: absolute; left: 0.9mm; top: 0.9mm; width: {{ $qrR }}mm; height: {{ $qrR }}mm; }

        /* ============================ VERSO ============================
           La face de la PLATEFORME, identique sur toutes les cartes : baobab,
           claustra, panneau du QR à gauche, colonne de droite — appel,
           accroche, adresse, signature — comme la maquette. */
        .panneau-verso {
            left: {{ $bord }}mm; top: {{ $panVy }}mm;
            width: {{ $panV }}mm; height: {{ $panV }}mm;
        }

        .qr-verso { position: absolute; left: 1mm; top: 1mm; width: {{ $qrV }}mm; height: {{ $qrV }}mm; }

        .v-colonne { position: absolute; left: {{ $colV }}mm; width: {{ $largeurColV }}mm; }

        /* L'appel et l'accroche forment UN bloc qui s'écoule : l'accroche
           vient de la configuration, sa longueur varie, et deux positions
           fixes l'auraient fait buter sur la ligne suivante. */
        .v-texte { top: {{ $panVy + 0.6 }}mm; }

        .v-appel {
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: 1.1pt;
            color: #E2B85A;
        }

        .v-accroche {
            margin-top: 1.6mm;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: -0.15pt;
            line-height: 1.16;
            color: #FFFCF6;
        }



        /* La signature : monogramme et nom, alignés sur le BAS du panneau —
           deux blocs qui partent et s'arrêtent sur les mêmes lignes se lisent
           comme une composition. */
        .v-marque { top: {{ $panVy + $panV - 6.2 }}mm; height: 6.2mm; }

        .v-marque img { position: absolute; left: 0; top: 0.2mm; width: 5.8mm; height: 5.8mm; }

        .v-marque span {
            position: absolute;
            left: 7.3mm; top: 0;
            line-height: 6.2mm;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: -0.5pt;
            color: #FFFCF6;
        }

        /* Pied, juste au-dessus de la lisière : la nature du service à
           gauche, l'adresse du site à droite, en or — la voix qui engage. */
        .v-pied {
            position: absolute;
            left: {{ $bord }}mm; top: {{ $basUtile - 4.8 }}mm;
            width: {{ $largeurUtile }}mm;
            border-collapse: collapse;
        }

        .v-pied td { padding: 0; vertical-align: bottom; }

        .v-nature {
            font-size: 5.6pt;
            letter-spacing: 1pt;
            color: #ABB3AB;
        }

        .v-site {
            text-align: right;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            color: #E2B85A;
        }
    </style>
</head>
<body>

{{-- ============================== RECTO ============================== --}}
<div class="page">
    <div class="fond"><img src="{{ $fondRecto }}" alt=""></div>

    <table class="haut">
        <tr>
            <td>
                <div class="nom">{{ $profile->full_name }}</div>
                @if ($profile->job_title)
                    <div class="fonction">{{ $profile->job_title }}</div>
                @endif
            </td>
            @if ($entreprise !== '')
                <td class="entreprise {{ mb_strlen($entreprise) <= 18 ? 'entreprise-courte' : 'entreprise-longue' }}">{{ mb_strtoupper($entreprise) }}</td>
            @endif
        </tr>
    </table>

    <div class="panneau panneau-recto">
        <img class="qr-recto" src="{{ $qrRecto }}" alt="{{ __('card.publique.qr_aria') }}">
    </div>
</div>

{{-- ============================== VERSO ==============================
     La face de la PLATEFORME. Aucune donnée du porteur : ce verso est
     rigoureusement identique sur toutes les cartes de tous les clients, et
     toujours en baobab, quelle que soit la variante du recto. --}}
<div class="page">
    <div class="fond"><img src="{{ $fondVerso }}" alt=""></div>

    <div class="panneau panneau-verso">
        <img class="qr-verso" src="{{ $qrVerso }}" alt="{{ __('card.publique.qr_aria') }}">
    </div>

    <div class="v-colonne v-texte">
        <div class="v-appel">{{ mb_strtoupper(config('landing.brand.card_cta')) }}</div>
        <div class="v-accroche">{{ config('landing.brand.tagline') }}</div>
    </div>
    <div class="v-colonne v-marque">
        <img src="{{ $monogramme }}" alt="">
        <span>{{ config('app.name') }}</span>
    </div>

    <table class="v-pied">
        <tr>
            <td class="v-nature">{{ mb_strtoupper(__('card.protocole')) }}</td>
            {{-- L'adresse vient de la configuration : en développement, la
                 dériver d'APP_URL ferait imprimer « 127.0.0.1 », et cela ne se
                 voit qu'une fois les cartes sorties. --}}
            <td class="v-site">{{ config('landing.brand.website') }}</td>
        </tr>
    </table>
</div>

</body>
</html>
