{{ __('emails.reinitialisation.titre') }}

{{ __('emails.reinitialisation.intro', ['marque' => config('app.name')]) }}

{{ __('emails.reinitialisation.bouton') }} :
{{ $resetUrl }}

{{ __('emails.reinitialisation.validite', ['minutes' => $ttlMinutes]) }}

{{ __('emails.reinitialisation.ignorer') }}

—
{{ config('app.name') }}
