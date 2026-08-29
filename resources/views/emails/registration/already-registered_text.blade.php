{{ __('emails.deja_inscrit.titre') }}

{{ __('emails.deja_inscrit.intro_texte', ['marque' => config('app.name')]) }}

{{ __('emails.deja_inscrit.si_vous') }}
{{ $loginUrl }}

{{ __('emails.deja_inscrit.oubli') }} {{ __('emails.deja_inscrit.oubli_lien') }} :
{{ $resetUrl }}

{{ __('emails.deja_inscrit.ignorer') }}

—
{{ config('app.name') }}
