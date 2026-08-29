{{ __('emails.commun.bonjour', ['nom' => $name]) }}

{{ __('emails.carte_publiee.intro') }}

{{ __('emails.commun.lien_a_partager') }} :
{{ $publicUrl }}

{{ __('emails.carte_publiee.telechargements') }}
{{ $dashboardUrl }}

—
{{ config('app.name') }}
