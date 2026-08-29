{{ __('emails.commun.bonjour', ['nom' => $name]) }}

{{ __('emails.abonnement_expire.intro', ['date' => $echeance]) }}

{{ __('emails.abonnement_expire.intactes_texte') }}

{{ __('emails.abonnement_expire.renouveler') }}

{{ __('emails.abonnement_expire.bouton') }} :
{{ $renewUrl }}
@if ($publicUrl)

{{ __('emails.abonnement_expire.adresse_conservee', ['url' => $publicUrl]) }}
@endif

—
{{ config('app.name') }}
