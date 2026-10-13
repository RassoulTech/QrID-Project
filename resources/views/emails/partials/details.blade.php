{{--
  Tableau de détails — reçu de paiement, récapitulatif d'échéance.

  Un vrai <table> et non des <div> : c'est la seule mise en page que tous les
  clients de messagerie rendent de la même façon, et ces lignes portent des
  chiffres qu'on relira peut-être devant un litige.

  Direction A : libellé en secondaire #56605A (14 px), valeur en encre
  #15211C (16 px, gras, chiffres tabulaires), filets #E0D5C1 entre les lignes,
  rayon 10. Sur un écran de 320 px, un libellé long passe à la ligne plutôt
  que de pousser la valeur hors de la carte.

  $lignes : tableau associatif libellé => valeur. Les valeurs sont échappées
  par Blade ; aucune n'est du HTML.
--}}
<table role="presentation" class="q-carte" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="width:100%;table-layout:fixed;margin:0 0 24px;border:1px solid #E0D5C1;border-radius:10px;border-collapse:separate;background-color:#FFFCF6;">
    @foreach ($lignes as $libelle => $valeur)
        <tr>
            <td class="q-texte2 q-trait" width="42%" valign="top"
                style="padding:12px 14px;font-size:14px;line-height:22px;color:#56605A;border-bottom:{{ $loop->last ? 'none' : '1px solid #E0D5C1' }};">
                {{ $libelle }}
            </td>
            <td class="q-texte q-trait" width="58%" align="right" valign="top"
                style="padding:12px 14px;font-size:16px;line-height:22px;font-weight:700;color:#15211C;font-variant-numeric:tabular-nums;word-break:break-word;overflow-wrap:break-word;border-bottom:{{ $loop->last ? 'none' : '1px solid #E0D5C1' }};">
                {{ $valeur }}
            </td>
        </tr>
    @endforeach
</table>
