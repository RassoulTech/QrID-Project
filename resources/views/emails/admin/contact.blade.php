@component('emails.layout', ['title' => __('emails.contact.titre', ['nom' => $contact->name])])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 20px;background:#F1F5F9;border-radius:8px;">
        <tr>
            <td style="padding:12px 14px;">
                <span style="font-size:12px;font-weight:bold;letter-spacing:.04em;color:#64748B;">
                    {{ __('emails.contact.bandeau') }}
                </span><br>
                <span style="font-size:18px;font-weight:bold;color:#1E293B;">{{ $motif }}</span>
            </td>
        </tr>
    </table>

    @include('emails.partials.details', ['lignes' => array_filter([
        __('emails.contact.lignes.nom') => $contact->name,
        __('emails.contact.lignes.adresse') => $contact->email,
        __('emails.contact.lignes.telephone') => $contact->phone,
        __('emails.contact.lignes.compte') => $contact->user_id ? __('emails.contact.oui') : __('emails.contact.non'),
        __('emails.contact.lignes.recu_le') => $contact->created_at?->translatedFormat(__('common.formats.date_heure')),
    ])])

    {{-- LE MESSAGE, tel qu'il a été écrit.
         nl2br sur une valeur ÉCHAPPÉE : les retours à la ligne sont conservés
         sans qu'aucun HTML saisi par un tiers ne puisse être interprété. --}}
    <div style="margin:0 0 24px;padding:14px 16px;border-left:3px solid #0B3B2E;background:#F8FAFC;font-size:15px;line-height:1.6;color:#1E293B;">
        {!! nl2br(e($contact->message)) !!}
    </div>

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {!! __('emails.contact_suite.reponse', ['adresse' => e($contact->email)]) !!}
    </p>
@endcomponent
