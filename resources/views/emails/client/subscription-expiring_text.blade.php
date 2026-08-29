{{ __('emails.commun.bonjour', ['nom' => $name]) }}

@if ($joursRestants <= 0)
{{ __('emails.abonnement_expirant.aujourdhui_texte', ['formule' => $formule]) }}
@elseif ($joursRestants === 1)
{{ __('emails.abonnement_expirant.demain_texte', ['formule' => $formule, 'date' => $echeance]) }}
@else
{{ __('emails.abonnement_expirant.dans_jours_texte', ['formule' => $formule, 'jours' => $joursRestants, 'date' => $echeance]) }}
@endif

{{ __('emails.abonnement_expirant.consequence') }}

{{ __('emails.abonnement_expirant.rien_supprime_texte') }}

{{ __('emails.abonnement_expirant.bouton') }} :
{{ $renewUrl }}

—
{{ config('app.name') }}
