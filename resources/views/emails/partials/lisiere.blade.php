{{--
  La lisière tissée de la direction A, en APLATS DE COULEUR.

  À l'écran, elle est un motif SVG : losanges latérite et or sur baobab. Un
  e-mail ne peut porter ni SVG ni image en data-URI (Gmail les retire), et une
  image hébergée serait bloquée par défaut chez la plupart des lecteurs. On la
  recompose donc en cellules de tableau colorées : baobab, latérite, baobab,
  or — le même rythme, sans un octet d'image.

  Largeurs en POURCENTAGE : la bande suit la carte, de 600 px à 320 px, sans
  jamais provoquer de défilement horizontal. Chaque cellule porte un &nbsp; en
  1 px : sans contenu, Outlook et Gmail écrasent une cellule vide à zéro.
--}}
@php
    $teintes = ['#0D3B2E', '#C2613A', '#0D3B2E', '#E2B85A'];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="width:100%;table-layout:fixed;border-collapse:collapse;">
    <tr>
        @for ($i = 0; $i < 40; $i++)
            <td width="2.5%" height="6" bgcolor="{{ $teintes[$i % 4] }}"
                style="width:2.5%;height:6px;background-color:{{ $teintes[$i % 4] }};font-size:1px;line-height:1px;mso-line-height-rule:exactly;">&nbsp;</td>
        @endfor
    </tr>
</table>
