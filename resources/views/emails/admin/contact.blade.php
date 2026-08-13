@component('emails.layout', ['title' => 'Message de '.$contact->name])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 20px;background:#F1F5F9;border-radius:8px;">
        <tr>
            <td style="padding:12px 14px;">
                <span style="font-size:12px;font-weight:bold;letter-spacing:.04em;color:#64748B;">
                    FORMULAIRE DE CONTACT
                </span><br>
                <span style="font-size:18px;font-weight:bold;color:#1E293B;">{{ $motif }}</span>
            </td>
        </tr>
    </table>

    @include('emails.partials.details', ['lignes' => array_filter([
        'Nom' => $contact->name,
        'Adresse' => $contact->email,
        'Téléphone' => $contact->phone,
        'Compte client' => $contact->user_id ? 'oui' : 'non',
        'Reçu le' => $contact->created_at?->translatedFormat('j F Y à H:i'),
    ])])

    {{-- LE MESSAGE, tel qu'il a été écrit.
         nl2br sur une valeur ÉCHAPPÉE : les retours à la ligne sont conservés
         sans qu'aucun HTML saisi par un tiers ne puisse être interprété. --}}
    <div style="margin:0 0 24px;padding:14px 16px;border-left:3px solid #0B5D3B;background:#F8FAFC;font-size:15px;line-height:1.6;color:#1E293B;">
        {!! nl2br(e($contact->message)) !!}
    </div>

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        <strong>Répondez directement à ce message</strong> : votre réponse partira
        vers {{ $contact->email }}.
    </p>
@endcomponent
