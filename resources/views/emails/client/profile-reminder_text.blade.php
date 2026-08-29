{{ __('emails.commun.bonjour', ['nom' => $name]) }}

@if ($rang === 1)
{{ __('emails.rappel_carte.premier') }}
@else
{{ __('emails.rappel_carte.second') }}
@endif

{{ __('emails.rappel_carte.gratuit') }}

{{ __('emails.rappel_carte.bouton') }} :
{{ $activateUrl }}

{{ __($rang === 1 ? 'emails.rappel_carte.rang_1' : 'emails.rappel_carte.rang_2') }}

—
{{ config('app.name') }}
