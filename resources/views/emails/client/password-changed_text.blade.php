{{ __('emails.commun.bonjour', ['nom' => $name]) }}

{{ __('emails.mot_de_passe_change.intro_texte', ['date' => $date]) }}

{{ __('emails.mot_de_passe_change.si_vous_texte') }}

{{ __('emails.mot_de_passe_change.sinon_texte') }}
{{ $resetUrl }}
@if ($ip)

{{ __('emails.mot_de_passe_change.ip', ['ip' => $ip]) }}
@endif

{{ __('emails.mot_de_passe_change.toujours_envoye') }}

—
{{ config('app.name') }}
