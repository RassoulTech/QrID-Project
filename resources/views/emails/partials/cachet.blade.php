{{--
  Le « cachet » de la direction A : l'état du message, en tête.

      @include('emails.partials.cachet', ['ton' => 'ok', 'libelle' => __('…')])

  Fond teinté, bordure de 1 px, rayon 6, libellé en capitales. LE LIBELLÉ
  PORTE L'INFORMATION, la couleur ne fait que la confirmer : un lecteur qui
  ne distingue pas le vert du rouge, ou un client qui inverse les couleurs,
  lit toujours « Paiement confirmé » ou « Paiement non abouti ».

  $ton : 'ok' (réussi), 'attente' (à surveiller), 'echec' (échoué).
  Contrastes mesurés : 6,8:1, 5,8:1 et 5,9:1 sur leur fond teinté.
--}}
@php
    [$fond, $trait, $encre] = match ($ton ?? 'ok') {
        'attente' => ['#F8EBCB', '#E6CF95', '#7A5300'],
        'echec' => ['#F7E1D9', '#E7B7A6', '#9B2F1A'],
        default => ['#E3F1E8', '#B7D9C4', '#145C3F'],
    };
    $classe = match ($ton ?? 'ok') {
        'attente' => 'q-attente',
        'echec' => 'q-echec',
        default => 'q-ok',
    };
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">
    <tr>
        <td class="{{ $classe }}" bgcolor="{{ $fond }}"
            style="background-color:{{ $fond }};border:1px solid {{ $trait }};border-radius:6px;padding:5px 10px;font-size:13px;line-height:18px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;color:{{ $encre }};">
            {{ $libelle }}
        </td>
    </tr>
</table>
