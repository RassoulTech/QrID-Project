@component('emails.layout', ['title' => $motif->titre()])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 20px;background:{{ $motif->estUrgent() ? '#FEF2F2' : '#F1F5F9' }};border-radius:8px;">
        <tr>
            <td style="padding:12px 14px;">
                <span style="font-size:12px;font-weight:bold;letter-spacing:.04em;color:{{ $motif->estUrgent() ? '#B91C1C' : '#64748B' }};">
                    {{ $motif->estUrgent() ? __('emails.alerte.action_requise') : __('emails.alerte.pour_information') }}
                </span><br>
                <span style="font-size:18px;font-weight:bold;color:#1E293B;">{{ $motif->titre() }}</span>
            </td>
        </tr>
    </table>

    @include('emails.partials.details', ['lignes' => $lignes])

    @if ($url)
        @include('emails.partials.bouton', [
            'url' => $url,
            'libelle' => __('emails.alerte.bouton'),
            'ton' => $motif->estUrgent() ? 'sombre' : 'vert',
        ])
    @endif

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __('emails.alerte.automatique') }}
    </p>
@endcomponent
