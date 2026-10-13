{{--
  Un encadré : ce qu'il faut voir d'un coup d'œil dans le message.

      @component('emails.partials.panneau', ['ton' => 'neutre'])
          ...contenu...
      @endcomponent

  $ton : 'neutre' (sable, par défaut), 'ok', 'attente', 'echec'. Le texte
  reste à l'encre #15211C sur toutes les teintes — plus de 12:1 partout. Un
  éventuel $surtitre s'écrit en capitales dans la couleur du statut (latérite
  pour le neutre), au-dessus d'un $titre facultatif.

  $filet : true pose le filet baobab de 3 px à gauche — la citation d'un
  message reçu, ou une précision qu'on veut détacher du corps.
--}}
@php
    $ton = $ton ?? 'neutre';
    [$fond, $trait, $encre, $classe, $classeEncre] = match ($ton) {
        'ok' => ['#E3F1E8', '#B7D9C4', '#145C3F', 'q-p-ok', 'q-t-ok'],
        'attente' => ['#F8EBCB', '#E6CF95', '#7A5300', 'q-p-attente', 'q-t-attente'],
        'echec' => ['#F7E1D9', '#E7B7A6', '#9B2F1A', 'q-p-echec', 'q-t-echec'],
        default => ['#F5EFE4', '#E0D5C1', '#A9481F', 'q-panneau', 'q-surtitre'],
    };
    $gauche = ($filet ?? false) ? '3px solid #0D3B2E' : '1px solid '.$trait;
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 24px;border-collapse:separate;">
    <tr>
        <td class="{{ $classe }} q-texte" bgcolor="{{ $fond }}"
            style="background-color:{{ $fond }};border:1px solid {{ $trait }};border-left:{{ $gauche }};border-radius:10px;padding:14px 16px;font-size:16px;line-height:26px;color:#15211C;">
            @isset($surtitre)
                <span class="{{ $classeEncre }}" style="display:block;font-size:13px;line-height:18px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:{{ $encre }};">{{ $surtitre }}</span>
            @endisset
            @isset($titre)
                <span class="q-texte" style="display:block;margin-top:2px;font-size:18px;line-height:26px;font-weight:800;color:#15211C;">{{ $titre }}</span>
            @endisset
            {{ $slot }}
        </td>
    </tr>
</table>
