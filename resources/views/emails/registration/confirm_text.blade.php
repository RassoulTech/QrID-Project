{{ __('emails.commun.bonjour', ['nom' => $name]) }}

{{ __('emails.confirmation.intro', ['jours' => $trialDays ?? 15]) }}

{{ __('emails.confirmation.bouton') }} :
{{ $verifyUrl }}

{{ __('emails.confirmation.validite', ['minutes' => $ttlMinutes]) }}

{{ __('emails.commun.ignorer') }}

—
{{ config('app.name') }}
