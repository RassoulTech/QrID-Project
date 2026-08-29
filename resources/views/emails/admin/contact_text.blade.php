{{ __('emails.contact.bandeau') }}
{{ $motif }}

{{ __('emails.contact.lignes.nom') }} : {{ $contact->name }}
{{ __('emails.contact.lignes.adresse') }} : {{ $contact->email }}
@if ($contact->phone)
{{ __('emails.contact.lignes.telephone') }} : {{ $contact->phone }}
@endif
{{ __('emails.contact.lignes.compte') }} : {{ $contact->user_id ? __('emails.contact.oui') : __('emails.contact.non') }}
{{ __('emails.contact.lignes.recu_le') }} : {{ $contact->created_at?->translatedFormat(__('common.formats.date_heure')) }}

{{ __('emails.contact_suite.message') }}
{{ $contact->message }}

{{ __('emails.contact_suite.reponse_texte', ['adresse' => $contact->email]) }}

—
{{ config('app.name') }}
