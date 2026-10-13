@component('emails.layout', ['title' => __('emails.contact.titre', ['nom' => $contact->name])])
    @component('emails.partials.panneau', [
        'ton' => 'neutre',
        'surtitre' => __('emails.contact.bandeau'),
        'titre' => $motif,
    ])
    @endcomponent

    @include('emails.partials.details', ['lignes' => array_filter([
        __('emails.contact.lignes.nom') => $contact->name,
        __('emails.contact.lignes.adresse') => $contact->email,
        __('emails.contact.lignes.telephone') => $contact->phone,
        __('emails.contact.lignes.compte') => $contact->user_id ? __('emails.contact.oui') : __('emails.contact.non'),
        __('emails.contact.lignes.recu_le') => $contact->created_at?->translatedFormat(__('common.formats.date_heure')),
    ])])

    {{-- LE MESSAGE, tel qu'il a été écrit.
         nl2br sur une valeur ÉCHAPPÉE : les retours à la ligne sont conservés
         sans qu'aucun HTML saisi par un tiers ne puisse être interprété.
         Le filet baobab à gauche dit « citation » sans un mot de plus. --}}
    @component('emails.partials.panneau', ['ton' => 'neutre', 'filet' => true])
        {!! nl2br(e($contact->message)) !!}
    @endcomponent

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {!! __('emails.contact_suite.reponse', ['adresse' => e($contact->email)]) !!}
    </p>
@endcomponent
