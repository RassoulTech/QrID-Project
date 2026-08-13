{{--
  Tableau de détails — reçu de paiement, récapitulatif d'échéance.

  Un vrai <table> et non des <div> : c'est la seule mise en page que tous les
  clients de messagerie rendent de la même façon, et ces lignes portent des
  chiffres qu'on relira peut-être devant un litige.

  $lignes : tableau associatif libellé => valeur. Les valeurs sont échappées
  par Blade ; aucune n'est du HTML.
--}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 24px;border:1px solid #E6EAF0;border-radius:8px;border-collapse:separate;">
    @foreach ($lignes as $libelle => $valeur)
        <tr>
            <td style="padding:10px 14px;font-size:13px;color:#64748B;border-bottom:{{ $loop->last ? 'none' : '1px solid #E6EAF0' }};">
                {{ $libelle }}
            </td>
            <td align="right" style="padding:10px 14px;font-size:14px;font-weight:bold;color:#1E293B;border-bottom:{{ $loop->last ? 'none' : '1px solid #E6EAF0' }};">
                {{ $valeur }}
            </td>
        </tr>
    @endforeach
</table>
