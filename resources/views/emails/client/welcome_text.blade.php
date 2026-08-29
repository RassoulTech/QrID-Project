{{ __('emails.commun.bonjour', ['nom' => $name]) }}

{{-- La directive doit débuter la ligne : Blade ignore un @if collé à un mot. --}}
{{ __('emails.bienvenue.essai', ['jours' => $trialDays]) }}
@if ($trialEndsAt)
{{ __('emails.bienvenue.essai_fin', ['date' => $trialEndsAt]) }}
@endif

{{ __('emails.bienvenue.etape') }}

{{ __('emails.bienvenue.bouton') }} :
{{ $createUrl }}
@if ($groupeUrl)

{{ __('emails.bienvenue.groupe_texte_brut') }}
{{ $groupeUrl }}
@endif

{{ __('emails.bienvenue.sans_paiement') }}

—
{{ config('app.name') }}
