FORMULAIRE DE CONTACT
{{ $motif }}

Nom : {{ $contact->name }}
Adresse : {{ $contact->email }}
@if ($contact->phone)
Téléphone : {{ $contact->phone }}
@endif
Compte client : {{ $contact->user_id ? 'oui' : 'non' }}
Reçu le : {{ $contact->created_at?->translatedFormat('j F Y à H:i') }}

--- MESSAGE ---

{{ $contact->message }}

---

Répondez directement à ce message : votre réponse partira vers {{ $contact->email }}.

—
{{ config('app.name') }}
