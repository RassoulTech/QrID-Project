{{--
  Bouton d'action. Écrit une fois, repris par tous les e-mails.

  BOUTON « À L'ÉPREUVE DES CLIENTS » : une cellule de tableau colorée qui
  porte un lien plein. Pourquoi un <a> et non un <button> : aucun client de
  messagerie ne soumet de formulaire. Le padding est sur le lien (Gmail,
  Apple Mail) ET, pour Outlook qui ignore le padding d'un lien, sur la
  cellule via mso-padding-alt : le bouton garde sa taille partout, et toute
  sa surface est cliquable là où le client le permet.

  CIBLE TACTILE : 14 + 20 + 14 = 48 px de haut, au-dessus des 44 px exigés.
  Un libellé long passe à la ligne au lieu de faire déborder la carte sur un
  écran de 320 px.

  $ton : 'vert' par défaut — le baobab de la marque, texte ivoire (12,1:1).
  'sombre' pour les messages de sécurité, où le vert de la marque suggérerait
  à tort une bonne nouvelle — l'encre #15211C, texte ivoire (16,1:1).
--}}
@php
    $sombre = ($ton ?? 'vert') === 'sombre';
    $fond = $sombre ? '#15211C' : '#0D3B2E';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:8px auto 24px;">
    <tr>
        <td align="center" bgcolor="{{ $fond }}" class="{{ $sombre ? 'q-bouton-sombre' : 'q-bouton' }}"
            style="background-color:{{ $fond }};border-radius:10px;mso-padding-alt:14px 28px;">
            <a href="{{ $url }}" target="_blank" class="q-bouton-lien"
               style="display:inline-block;padding:14px 28px;font-family:'Schibsted Grotesk',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:16px;line-height:20px;font-weight:700;color:#FFFCF6;text-decoration:none;text-align:center;border-radius:10px;">{{ $libelle }}</a>
        </td>
    </tr>
</table>
