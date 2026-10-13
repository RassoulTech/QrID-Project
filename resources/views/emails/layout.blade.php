{{--
  Gabarit d'e-mail unique du produit — direction A « Teranga institutionnel ».
  Tous les Mailables en héritent :

      @component('emails.layout', ['title' => 'Sujet'])
          ...contenu...
      @endcomponent

  ═══════════════════════════════════════════════════════════════════════
  CONTRAINTES DE DÉLIVRABILITÉ, À NE JAMAIS ENFREINDRE
  ═══════════════════════════════════════════════════════════════════════
  Tableaux HTML, CSS strictement en ligne, aucune police ni image externe,
  aucun SVG, aucune image en data-URI (Gmail les retire), aucun JavaScript.
  La signature de marque passe donc par des APLATS DE COULEUR : le bandeau
  baobab, la lisière en cellules latérite et or, le fond sable, la carte
  ivoire. Rien ne disparaît quand les images sont bloquées, puisqu'il n'y
  en a aucune.

  ═══════════════════════════════════════════════════════════════════════
  LA PALETTE — reprise de la direction A, contrastes mesurés
  ═══════════════════════════════════════════════════════════════════════
    fond sable #F5EFE4 · carte ivoire #FFFCF6 · traits #E0D5C1
    texte #15211C (16,1:1 sur ivoire) · secondaire #56605A (6,3:1)
    liens #146A50 (6,4:1) · bouton baobab #0D3B2E, texte ivoire (12,1:1)
    surtitre latérite #A9481F (5,6:1 sur ivoire)
  Les statuts (réussi, en attente, échoué) vivent dans emails.partials.cachet
  et emails.partials.panneau.

  ═══════════════════════════════════════════════════════════════════════
  LE MODE SOMBRE DES CLIENTS
  ═══════════════════════════════════════════════════════════════════════
  Deux comportements coexistent, et le gabarit tient dans les deux :
    - Apple Mail, Outlook macOS : ils lisent color-scheme et la requête
      prefers-color-scheme ci-dessous. Les classes q-* y reçoivent la palette
      sombre de la direction A. Ce bloc est un ENRICHISSEMENT : un client qui
      l'ignore (Gmail) garde le rendu clair, entièrement lisible.
    - Gmail et Outlook mobiles : ils INVERSENT eux-mêmes les couleurs. Chaque
      couple texte/fond a été choisi avec un écart de luminance franc, si bien
      qu'il reste lisible une fois inversé — c'est le seul levier qu'ils
      laissent.

  LA PILE DE POLICES commence par Schibsted Grotesk (installée chez peu de
  lecteurs, jamais chargée ici) puis retombe sur les polices système. Outlook
  pour Windows, qui remplace une pile inconnue par Times, reçoit Arial par un
  commentaire conditionnel.

  Largeur : 600 px au plus, fluide en dessous. Le texte de lecture est en
  16 px, les mentions en 14 px, le pied en 13 px — tous au-dessus de 4,5:1.
--}}
@php
    $police = "'Schibsted Grotesk',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title ?? config('app.name') }}</title>
    <!--[if mso]>
    <style>body, table, td, p, a, h1, span { font-family: Arial, sans-serif !important; }</style>
    <![endif]-->
    <style>
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
        @media (prefers-color-scheme: dark) {
            .q-fond { background-color: #0C1713 !important; }
            .q-carte { background-color: #13211C !important; border-color: #2A3D35 !important; }
            .q-bandeau { background-color: #0B3328 !important; }
            .q-texte { color: #F2ECE0 !important; }
            .q-texte2 { color: #ABB3AB !important; }
            .q-surtitre { color: #EE9B72 !important; }
            .q-lien { color: #6FD1A8 !important; }
            .q-trait { border-color: #2A3D35 !important; }
            .q-panneau { background-color: #1B2D26 !important; border-color: #2A3D35 !important; }
            .q-bouton { background-color: #6FD1A8 !important; }
            .q-bouton-lien { color: #0B1A15 !important; }
            .q-bouton-sombre { background-color: #F2ECE0 !important; }
            .q-ok { background-color: #173A2C !important; border-color: #275740 !important; color: #8FE0B9 !important; }
            .q-attente { background-color: #3D3115 !important; border-color: #5E4C1F !important; color: #F2CC74 !important; }
            .q-echec { background-color: #45201A !important; border-color: #6B3427 !important; color: #F6A68E !important; }
            .q-p-ok { background-color: #173A2C !important; border-color: #275740 !important; }
            .q-p-attente { background-color: #3D3115 !important; border-color: #5E4C1F !important; }
            .q-p-echec { background-color: #45201A !important; border-color: #6B3427 !important; }
            .q-t-ok { color: #8FE0B9 !important; }
            .q-t-attente { color: #F2CC74 !important; }
            .q-t-echec { color: #F6A68E !important; }
        }
        [data-ogsc] .q-texte { color: #F2ECE0 !important; }
        [data-ogsc] .q-texte2 { color: #ABB3AB !important; }
        [data-ogsc] .q-lien { color: #6FD1A8 !important; }
        [data-ogsb] .q-fond { background-color: #0C1713 !important; }
        [data-ogsb] .q-carte { background-color: #13211C !important; }
    </style>
</head>
<body class="q-fond" style="margin:0;padding:0;width:100%;background-color:#F5EFE4;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <table role="presentation" class="q-fond" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F5EFE4"
           style="width:100%;background-color:#F5EFE4;">
        <tr>
            <td align="center" style="padding:24px 12px 32px;font-family:{{ $police }};">
                <!--[if mso]><table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                <table role="presentation" class="q-carte" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFCF6"
                       style="width:100%;max-width:600px;background-color:#FFFCF6;border:1px solid #E0D5C1;border-radius:16px;border-collapse:separate;overflow:hidden;">
                    {{-- En-tête : le bandeau baobab, le nom en ivoire. --}}
                    <tr>
                        <td class="q-bandeau" bgcolor="#0D3B2E" style="background-color:#0D3B2E;padding:22px 28px 20px;border-radius:15px 15px 0 0;">
                            <span style="font-family:{{ $police }};font-size:24px;line-height:28px;font-weight:800;letter-spacing:-0.5px;color:#FFFCF6;">{{ config('app.name') }}</span>
                        </td>
                    </tr>

                    {{-- La lisière : elle ferme le bandeau, comme sur la carte. --}}
                    <tr>
                        <td style="padding:0;">
                            @include('emails.partials.lisiere')
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td class="q-texte" style="padding:32px 28px 28px;font-family:{{ $police }};font-size:16px;line-height:26px;color:#15211C;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>

                {{-- Pied, sur le sable : hors de la carte, comme le pied des pages. --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">
                    <tr>
                        <td class="q-texte2" style="padding:20px 28px 0;font-family:{{ $police }};font-size:13px;line-height:20px;color:#56605A;">
                            &copy; {{ date('Y') }} <strong style="color:inherit;">{{ config('app.name') }}</strong> — {{ __('emails.commun.pied_produit') }}<br>
                            {{ __('emails.commun.pied_raison') }}
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
